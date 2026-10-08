<?php


namespace app\common\task;

use think\facade\Log;
use think\facade\Console;
use happy\admin\libs\task\Task;
use app\model\system\SystemModule;
use think\db\exception\DbException;
use happy\admin\libs\extend\RuntimeExtend;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

class SiteMap extends Task
{
    protected array $sitemap_class = ['content', 'tag'];
    /**
     * 单线程
     * @var bool
     */
    public bool $onOneServer = true;
    
    public int $expiresAt = 36000;
    
    /**
     * 生成sitemap
     * @return void
     */
    public function handle(): void
    {
        if (!RuntimeExtend::checkRoute()) {
            Log::error('当前任务【sitemap】可执行条件不足');
            return;
        }
        Console::call('sitemap:column', ['xml']);
        Console::call('sitemap:column', ['txt']);
        $modules = (new SystemModule())->where(['status' => 1])->column('title', 'dir');
        foreach ($modules as $dir => $title) {
            try {
                if (sys_config($dir . '.is_open_sitemap', 0)) {
                    foreach ($this->sitemap_class as $class) {
                        Console::call('sitemap:build', ['xml', '--module=' . $dir, '--class=' . $class]);
                        sleep(5);
                        Console::call('sitemap:build', ['txt', '--module=' . $dir, '--class=' . $class]);
                        sleep(3);
                    }
                }
            } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
                Log::error($e->getMessage());
            }
        }
        Console::call('sitemap:index');
    }
}
