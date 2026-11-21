<?php


namespace app\job\system;

use think\queue\Job;
use think\facade\Log;
use think\facade\Cache;
use app\model\system\SystemSpiderLogs;

/**
 * 记录蜘蛛爬取日志（Redis List 缓冲 + 批量入库）
 *
 * 触发入库的条件（满足其一）：
 * 1. 条数达到 BATCH_SIZE_TRIGGER（高流量站点，快速入库）
 * 2. 距首批日志入队超过 MAX_BUFFER_SECONDS（低流量站点，避免滞留导致小时汇总漏统计）
 * 另提供 flushPending() 供小时汇总命令在统计前强制清空缓冲，彻底避免跨小时遗漏。
 */
class RecordSpiderLog
{
    private const TEMP_LOG_KEY_RAW = 'spider_logs_temp_batch';                     // Redis List的原始键名
    private const TEMP_LOG_KEY_RAW_ERROR_NUM = 'spider_logs_temp_batch_error_num'; // 失败计数器
    private const FIRST_PUSH_TIME_KEY_RAW = 'spider_logs_temp_batch_first_time';   // 首批日志入队时间（时间触发用）
    private const BATCH_SIZE_TRIGGER = 50;                                         // 条数阈值
    private const MAX_BUFFER_SECONDS = 600;                                        // 缓冲最长时间（10分钟），超时强制入库

    /**
     * @param Job $job
     * @param array $data
     * @return void
     */
    public function fire(Job $job, array $data): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            $prefixedKey = $this->getPrefixedKey();

            // 将数据序列化后添加到 Redis List 的尾部
            $serializedData = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $redis->rPush($prefixedKey, $serializedData);

            // 记录首批日志入队时间（仅首次设置，避免每条都刷新导致永不超时）
            $firstTimeKey = $this->getPrefixedKey(self::FIRST_PUSH_TIME_KEY_RAW);
            if (!$redis->exists($firstTimeKey)) {
                $redis->set($firstTimeKey, time(), ['EX' => 86400]);
            }

            // 条数阈值 或 时间阈值，任一满足即触发入库
            $listLength = $redis->lLen($prefixedKey);
            if ($listLength >= self::BATCH_SIZE_TRIGGER || $this->isBufferTimeout($redis, $firstTimeKey)) {
                $this->flushPending();
            }

            // 成功后删除队列中的任务
            $job->delete();
        } catch (\Exception $e) {
            Log::error('记录蜘蛛日志到缓存失败：' . $e->getMessage());
            if ($job->attempts() > 3) {
                $job->delete(); // 尝试超过3次失败后删除任务
            }
        }
    }

    /**
     * 判断缓冲是否已超时（低流量站点兜底触发）
     *
     * @param mixed $redis
     * @param string $firstTimeKey
     * @return bool
     */
    private function isBufferTimeout($redis, string $firstTimeKey): bool
    {
        $firstTime = (int)$redis->get($firstTimeKey);
        return $firstTime > 0 && (time() - $firstTime) >= self::MAX_BUFFER_SECONDS;
    }

    /**
     * 强制将 Redis 中暂存的日志全部入库（供小时汇总命令调用）
     *
     * @return int 入库条数，0 表示无待处理数据
     */
    public function flushPending(): int
    {
        $redis = Cache::store('redis')->handler();
        $prefixedKey = $this->getPrefixedKey();
        $firstTimeKey = $this->getPrefixedKey(self::FIRST_PUSH_TIME_KEY_RAW);

        // 原子取出全部日志并清空 List（避免并发重复消费）
        $script = "
            local logs = redis.call('LRANGE', KEYS[1], 0, -1)
            redis.call('DEL', KEYS[1])
            redis.call('DEL', KEYS[2])
            return logs
        ";
        $logsJsonArray = $redis->eval($script, [$prefixedKey, $firstTimeKey], 2);

        if (empty($logsJsonArray)) {
            return 0;
        }

        $model = new SystemSpiderLogs();
        try {
            $logs = array_map(static function ($jsonStr) {
                $log = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);
                unset($log['id']);  // 移除 id 字段，让数据库使用自增ID
                $log['url'] = strlen($log['url'] ?? '') > 500 ? substr($log['url'], 0, 500) : ($log['url'] ?? '');
                return $log;
            }, $logsJsonArray);

            $model->startTrans();
            $model->insertAll($logs);
            Cache::delete(self::TEMP_LOG_KEY_RAW_ERROR_NUM);
            $model->commit();

            return count($logs);
        } catch (\Exception $e) {
            $model->rollback();
            Log::error('批量插入蜘蛛日志失败：' . $e->getMessage());

            // 失败时把数据放回 List 头部（保持原有先后顺序），等待下次重试
            foreach (array_reverse($logsJsonArray) as $jsonStr) {
                $redis->lPush($prefixedKey, $jsonStr);
            }
            // 恢复首批时间，确保超时触发仍然有效
            if (!$redis->exists($firstTimeKey)) {
                $redis->set($firstTimeKey, time() - self::MAX_BUFFER_SECONDS, ['EX' => 86400]);
            }

            $errorKey = self::TEMP_LOG_KEY_RAW_ERROR_NUM;
            $errorCount = (int)Cache::inc($errorKey);
            if ($errorCount >= 5) {
                Cache::delete($errorKey);
                // 超过重试上限：为避免无限占用内存，丢弃这批数据并记录错误日志
                foreach (array_splice($logsJsonArray, 0) as $jsonStr) {
                    Log::error('蜘蛛日志重试超限已丢弃：' . $jsonStr);
                }
                $redis->del($prefixedKey);
            }
            return 0;
        }
    }

    /**
     * 获取带前缀的Redis键名
     *
     * @param string $rawKey
     * @return string
     */
    private function getPrefixedKey(string $rawKey = self::TEMP_LOG_KEY_RAW): string
    {
        $prefix = (string)config('cache.stores.redis.prefix'); // 默认为空字符串
        return $prefix . $rawKey;
    }
}
