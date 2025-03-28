<?php

namespace app\common;

use app\common\command\push\IndexNow;
use app\common\command\Sitemap;
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
