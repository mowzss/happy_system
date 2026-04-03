<?php

namespace app\job\system;

use app\model\system\SystemSpiderLogs;
use think\facade\Cache;
use think\queue\Job;

// 使用Cache门面

/**
 * 记录蜘蛛爬取日志（按条数批量处理版 - 使用Redis List）
 */
class RecordSpiderLog
{
    private const TEMP_LOG_KEY_RAW = 'spider_logs_temp_batch'; // Redis List的原始键名
    private const BATCH_SIZE_TRIGGER = 20; // 当缓存达到此数量时，触发批量插入

    /**
     * 获取带前缀的Redis键名
     * @return string
     */
    private function getPrefixedKey(): string
    {
        // 获取当前配置的缓存前缀
        $prefix = Cache::getStoreConfig('redis', 'prefix', ''); // 默认为空字符串
        // 拼接前缀和原始键名
        return $prefix . self::TEMP_LOG_KEY_RAW;
    }

    /**
     * @param Job $job
     * @param array $data
     * @return void
     */
    public function fire(Job $job, array $data): void
    {
        $prefixed_key = $this->getPrefixedKey();

        try {
            // --- 修正：使用Redis原生命令LPUSH/RPUSH ---
            // 将数据序列化后添加到Redis List的尾部
            $serialized_data = json_encode($data, JSON_UNESCAPED_UNICODE);
            Cache::store('redis')->handler()->rPush($prefixed_key, $serialized_data); // rPush 添加到列表末尾

            // 获取列表当前长度
            $list_length_after_push = Cache::store('redis')->handler()->lLen($prefixed_key);

            // 检查是否达到了批量处理的阈值
            if ($list_length_after_push >= self::BATCH_SIZE_TRIGGER) {

                // --- 触发批量处理 ---
                $this->processBatchLogs();
            }

            // 成功后删除队列中的任务
            $job->delete();

        } catch (\Exception $e) {
            if ($job->attempts() > 3) {
                $job->delete(); // 尝试超过3次失败后删除任务
            }
            trace("记录蜘蛛日志到缓存失败：" . $e->getMessage(), 'error');
        }
    }

    /**
     * 执行批量插入逻辑
     */
    private function processBatchLogs(): void
    {
        $prefixed_key = $this->getPrefixedKey();

        // 获取所有暂存在Redis中的日志数据，并原子性地清除它们
        $get_and_clear_script = "
            local logs = redis.call('LRANGE', KEYS[1], 0, -1)
            redis.call('DEL', KEYS[1]) -- 原子性地清除整个列表
            return logs
        ";
        $logsToInsertJsonArray = Cache::store('redis')->handler()->eval($get_and_clear_script, [$prefixed_key], 1);

        if (empty($logsToInsertJsonArray)) {
            return; // 如果没有数据，直接返回
        }
        $model = new SystemSpiderLogs();
        try {

            $model->startTrans(); // 开启事务

            // 将JSON字符串数组转换回关联数组
            $logsToInsert = array_map(function ($json_str) {
                return json_decode($json_str, true);
            }, $logsToInsertJsonArray);

            // 批量插入数据
            $model->insertAll($logsToInsert);

            $model->commit(); // 提交事务

            trace("成功批量插入 " . count($logsToInsert) . " 条蜘蛛日志。", 'info');

        } catch (\Exception $e) {
            $model->rollback(); // 回滚事务

            trace("批量插入蜘蛛日志失败：" . $e->getMessage(), 'error');

            // --- 失败后处理 ---
            // 将失败的数据重新放回缓存，等待后续重试
            // 注意：如果失败是永久性的（如数据格式错误），会导致无限重试
            foreach ($logsToInsertJsonArray as $log_json) {
                Cache::store('redis')->handler()->rPush($prefixed_key, $log_json); // 重新放入列表
            }
        }
    }
}
