<?php

namespace app\model\system;

use mowzs\lib\Model;
use think\db\Query;

class SystemTasks extends Model
{
    /**
     * 获取任务列表
     * @return array
     */
    // 修改SystemTasks获取条件
    public static function getTaskList(): array
    {
        return self::where(function (Query $query) {
            $query->whereTime('next_time', '<=', date('Y-m-d H:i:s'), 'OR')
                ->whereNull('next_time', 'OR');
        })->where('status', 1)
            ->order('list', 'asc')
            ->column('id,title,exptime,task,data', 'id');
    }

}
