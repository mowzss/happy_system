<?php

namespace app\job\system;

use app\model\system\SystemSpiderLogs;
use think\queue\Job;

/**
 * 记录蜘蛛爬取日志
 */
class RecordSpiderLog
{
    /**
     * @param Job $job
     * @param $data
     * @return void
     */
    public function fire(Job $job, $data): void
    {
        try {
            // 插入数据库
            $model = new SystemSpiderLogs();
            $model->insert($data);
            // 成功后删除队列中的任务
            $job->delete();

        } catch (\Exception $e) {
            if ($job->attempts() > 3) {
                // 尝试超过3次失败后删除任务
                $job->delete();
            }
            // 可记录日志用于排查问题
            trace("记录蜘蛛日志失败：" . $e->getMessage(), 'error');
        }
    }
}
