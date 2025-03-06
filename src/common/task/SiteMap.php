<?php

namespace app\common\task;

use app\model\system\SystemModule;
use mowzs\lib\task\Task;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Console;
use think\facade\Log;

class SiteMap extends Task
{
    protected array $sitemap_class = ['content', 'tag', 'column'];

    /**
     * 生成sitemap
     * @return void
     */
    public function handle(): void
    {
        $modules = (new SystemModule())->where(['status' => 1])->column('title', 'dir');
        foreach ($modules as $dir => $title) {
            try {
                if (sys_config($dir . '.is_open_sitemap', 0)) {
                    foreach ($this->sitemap_class as $class) {
                        Console::call('task:sitemap', ['xml', '--module' => $dir, '--class' => $class]);
                        sleep(60);
                    }
                }
            } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
                Log::error($e->getMessage());
            }
        }
    }
}
