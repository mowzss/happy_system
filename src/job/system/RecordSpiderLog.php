<?php

namespace app\job\system;

use app\model\system\SystemSpiderLogs;
use think\facade\Cache;
use think\facade\Log;
use think\queue\Job;

/**
 * 记录蜘蛛爬取日志（按条数批量处理版）
 */
class RecordSpiderLog
{
    private const TEMP_LOG_KEY = 'job_spider_logs_temp_batch';
    private const BATCH_SIZE_TRIGGER = 500; // 当缓存达到此数量时，触发批量插入

    /**
     * @param Job $job
     * @param array $data
     * @return void
     */
    public function fire(Job $job, array $data): void
    {

        try {
            // 将数据添加到Redis的列表中
            // 注意：Cache::push 不一定返回长度，所以先添加，再获取长度
            Cache::push(self::TEMP_LOG_KEY, $data);

            // --- 修正：获取列表当前长度 ---
            $list_length_after_push = Cache::handler()->lLen(self::TEMP_LOG_KEY); // 使用原生Redis命令获取长度

            // 检查是否达到了批量处理的阈值
            if ($list_length_after_push >= self::BATCH_SIZE_TRIGGER) {

                // --- 关键：触发批量处理 ---
                $this->processBatchLogs();
            }

            // 成功后删除队列中的任务
            $job->delete();

        } catch (\Exception $e) {
            if ($job->attempts() > 3) {
                $job->delete(); // 尝试超过3次失败后删除任务
            }
            Log::error("记录蜘蛛日志到缓存失败：" . $e->getMessage());
        }
    }

    /**
     * 执行批量插入逻辑
     */
    private function processBatchLogs(): void
    {
        // 获取所有暂存在Redis中的日志数据
        // 注意：这里使用 lRange 并配合 Lua 脚本原子性地获取和清除，防止并发问题
        $get_and_clear_script = "
            local logs = redis.call('LRANGE', KEYS[1], 0, -1)
            redis.call('DEL', KEYS[1]) -- 原子性地清除整个列表
            return logs
        ";
        $logsToInsert = Cache::handler()->eval($get_and_clear_script, 1, self::TEMP_LOG_KEY);

        if (empty($logsToInsert)) {
            return; // 如果没有数据，直接返回
        }
        $model = new SystemSpiderLogs();
        try {

            $model->startTrans(); // 开启事务

            // 将 JSON 字符串转换回数组
            $logsToInsert = array_map(function ($json_str) {
                return json_decode($json_str, true);
            }, $logsToInsert);
            // 批量插入数据
            $model->insertAll($logsToInsert);
            $model->commit(); // 提交事务
            Log::info("成功批量插入 " . count($logsToInsert) . " 条蜘蛛日志。", 'info');
        } catch (\Exception $e) {
            $model->rollback(); // 回滚事务
            Log::error("批量插入蜘蛛日志失败：" . $e->getMessage(), 'error');
            foreach ($logsToInsert as $log) {
                Cache::push(self::TEMP_LOG_KEY, $log); // 重新序列化
            }
        }
    }
}
