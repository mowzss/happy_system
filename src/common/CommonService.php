<?php

namespace app\common;

use app\common\command\task\Sitemap;
use think\Service;

class CommonService extends Service
{
    public function boot(): void
    {
        // 注册命令行
        $this->registerCommand();
    }

    /**
     * 注册命令行
     * @return void
     */
    protected function registerCommand(): void
    {
        $this->commands([
            Sitemap::class
        ]);
    }
}
