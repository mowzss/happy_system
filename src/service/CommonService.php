<?php

namespace app\service;

use app\command\module\ContentSync;
use app\command\system\cloud\UploadStaticToCloud;
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
use app\job\system\RecordSpiderLog;
use think\facade\Queue;

class CommonService extends \think\Service
{
    public function register()
    {
    }

    public function boot(): void
    {
        // 注册蜘蛛信息中间件
//        $this->app->middleware->add(SpiderDetectMiddleware::class);
        // 注册命令行
        $this->registerCommand();
        // 注册事件
        $this->registerEvent();
    }

    /**
     * @return void
     */
    private function registerEvent(): void
    {
        $this->app->event->listen('HomeControllerInit', function () {
            $spiders = $this->app->config->get('spiders.list', []);
            $userAgent = $this->app->request->server('HTTP_USER_AGENT', '');
            $ip = $this->app->request->ip();
            foreach (array_keys($spiders) as $pattern) {
                if (stripos($userAgent, $pattern) !== false) {
                    $isSpider = true;
                    // 匹配到蜘蛛，记录日志
                    $spiderCode = $spiders[$pattern];
                    $url = $this->app->request->url();

                    $data = [
                        'name' => $spiderCode,
                        'url' => $url,
                        'ip' => $ip,
                        'module' => $this->app->request->layer(),
                        'user_agent' => $userAgent,
                        'create_time' => time()
                    ];
                    Queue::push(RecordSpiderLog::class, $data);
                    break;
                }
            }
            //针对出搜索蜘蛛外不支持 cookies的访问，进行延时
//            if (!isset($isSpider)) {
//                $this->app->cookie->set('__erds_id', md5($this->app->request->ip() . $userAgent));
//                if ($this->app->cookie->get('__erds_id', 0) != md5($this->app->request->ip() . $userAgent)) {
//                    sleep(61);
//                }
//            }
        });
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
            HourlyReport::class,
            UploadStaticToCloud::class,
        ]);
    }
}
