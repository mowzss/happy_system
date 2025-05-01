<?php

namespace app\common\task;

use mowzs\lib\extend\RuntimeExtend;
use mowzs\lib\task\Task;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Console;
use think\facade\Log;

class IndexNow extends Task
{
    /**
     * 单线程
     * @var bool
     */
    public bool $onOneServer = true;

    public int $expiresAt = 36000;

    /**
     *
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function handle(): void
    {
        if (!RuntimeExtend::checkRoute()) {
            Log::error('当前任务【indexnow】可执行条件不足');
            return;
        }
        try {
            if (!empty(sys_config('p_index_now.is_open'))) {
                $models = explode(',', sys_config('p_index_now.open_module'));
                foreach ($models as $model) {
                    Console::call('indexnow:push', [$model]);
                    $this->app->log->log('task', $model . '模块推送索引成功');
                    sleep(5);
                }

            }
        } catch (DataNotFoundException|DbException $e) {
            Log::error($e->getMessage());
        }
    }
}
