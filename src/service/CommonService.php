<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Queue;
use app\job\system\RecordSpiderLog;
use app\command\system\xuns\XunsAdd;
use app\command\system\xuns\XunsClean;
use app\command\system\spider\ClearLogs;
use app\command\system\nav\NavRestNodeUrl;
use app\command\system\spider\DailyReport;
use app\command\system\spider\HourlyReport;
use app\command\system\sitemap\SitemapBuild;
use app\command\system\sitemap\SitemapIndex;
use app\command\system\indexnow\IndexNowPush;
use app\command\system\sitemap\SitemapColumn;
use app\command\system\indexnow\IndexNowClean;
use app\command\system\cloud\UploadStaticToCloud;

class CommonService extends \think\Service
{
    public function boot(): void
    {
        // 注册命令行
        $this->registerCommand();
        // 注册事件
        $this->registerEvent();
        // 注册中间件
        $this->registerMiddleware();
    }
    
    /**
     * 注册中间件
     * @return void
     */
    private function registerMiddleware(): void
    {
    
    }
    
    /**
     * 注册事件
     * @return void
     */
    private function registerEvent(): void
    {
        $this->app->event->listen('HomeControllerInit', function () {
            $this->registerSpidersLog();
        });
    }
    
    /**
     * 注册命令行
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
            ClearLogs::class,
            DailyReport::class,
            HourlyReport::class,
            UploadStaticToCloud::class,
            NavRestNodeUrl::class,
        ]);
    }
    
    /**
     * 注册蜘蛛日志
     * @return void
     */
    public function registerSpidersLog(): void
    {
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
                    //截取url长度 不超500
                    'url' => strlen($url) > 500 ? substr($url, 0, 500) : $url,
                    'ip' => $ip,
                    'module' => $this->app->request->layer(),
                    'domain' => $this->app->request->domain(),
                    'user_agent' => $userAgent,
                    'create_time' => time(),
                ];
                Queue::push(RecordSpiderLog::class, $data);
                break;
            }
        }
    }
}
