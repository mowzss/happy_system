<?php

namespace app\common;

use app\command\system\IndexNow;
use app\command\system\Sitemap;
use think\Service;

class CommonService extends Service
{
    public function register()
    {
    }

    public function boot(): void
    {
        // 注册命令行
        $this->registerCommand();
    }

    /**
     * @return void
     */
    private function registerCommand(): void
    {
        $this->commands([
            Sitemap::class,
            IndexNow::class
        ]);
    }
}
