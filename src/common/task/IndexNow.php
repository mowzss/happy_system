<?php

namespace app\common\task;

use think\facade\Log;
use think\facade\Console;
use happy\admin\libs\task\Task;
use think\db\exception\DbException;
use happy\admin\libs\extend\RuntimeExtend;
use think\db\exception\DataNotFoundException;

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
     * @throws \Throwable
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
                    if ((int)sys_config('is_wap_domain') === 1) {
                        Console::call('indexnow:push', [$model, '--domain wap']);
                    }
                    $this->app->log->log('task', $model . '模块推送索引成功');
                    sleep(5);
                }
                
            }
        } catch (DataNotFoundException|DbException $e) {
            Log::error($e->getMessage());
        }
    }
}
