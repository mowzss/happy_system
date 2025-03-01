<?php

namespace app\model\system;

use mowzs\lib\Model;

/**
 * 系统任务日志模型
 */
class SystemTaskLogs extends Model
{
    /**
     * 获取指定任务的日志
     * @param $taskId
     * @return SystemTaskLogs[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getLogsByTaskId($taskId)
    {
        return self::where('task_id', $taskId)->order('created_at', 'desc')->select();
    }
}
