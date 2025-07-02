<?php

namespace app\common;

use app\command\module\ContentSync;
use app\command\system\indexnow\IndexNowClean;
use app\command\system\indexnow\IndexNowPush;
use app\command\system\sitemap\SitemapBuild;
use app\command\system\sitemap\SitemapColumn;
use app\command\system\sitemap\SitemapIndex;
use app\command\system\spider\ClearLogs;
use app\command\system\spider\DailyReport;
use app\command\system\spider\HourlyReport;
use app\command\system\xuns\XunsAdd;
use app\command\system\xuns\XunsClean;
use app\common\middleware\system\SpiderDetectMiddleware;
use think\Service;

class CommonService extends Service
{
    public function register()
    {
    }

    public function boot(): void
    {
        // 注册蜘蛛信息中间件
        $this->app->middleware->add(SpiderDetectMiddleware::class);
        // 注册命令行
        $this->registerCommand();
    }

    /**
     * @return void
     */
    private function registerCommand(): void
    {
        $this->commands([
            SitemapColumn::class,
            SitemapBuild::class,
            SitemapIndex::class,
            IndexNowPush::class,
            IndexNowClean::class,
            XunsAdd::class,
            XunsClean::class,
            ContentSync::class,
            ClearLogs::class,
            DailyReport::class,
            HourlyReport::class
        ]);
    }
}
