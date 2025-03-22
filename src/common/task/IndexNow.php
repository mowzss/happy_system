<?php

namespace app\common\task;

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
        try {
            if (!empty(sys_config('p_index_now.is_open'))) {
                $models = explode(',', sys_config('p_index_now.open_module'));
                foreach ($models as $model) {
                    Console::call('push:indexnow', ['--module=' . $model]);
                    sleep(5);
                }

            }
        } catch (DataNotFoundException|DbException $e) {
            Log::error($e->getMessage());
        }
    }
}
