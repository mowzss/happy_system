<?php

namespace app\common;

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
            \app\common\command\task\Sitemap::class
        ]);
    }
}
