<?php

namespace app\common\middleware\system;

use app\common\job\RecordSpiderLog;
use think\facade\Queue;

class SpiderDetectMiddleware
{
    // 支持识别的蜘蛛列表（含常见国内外搜索引擎）
    private $spiders = [
        'Googlebot' => 'Google',
        'Bingbot' => 'Bing',
        'Baiduspider' => '百度',
        'Sogou' => '搜狗',
        '360Spider' => '360搜索',
        'HaosouSpider' => '好搜',
        'YisouSpider' => '神马',
        'Bytespider' => '头条',
        'Toutiaospider' => '头条',
        'ToutiaoSpider' => '头条',
        'DouyinSpider' => '头条', // 抖音爬虫
    ];

    public function handle($request, \Closure $next)
    {
        $userAgent = $request->header('user-agent', '');
        $ip = $request->ip();
        $url = $request->baseUrl();

        $spiderName = $this->detectSpider($userAgent);

        if ($spiderName) {
            $logData = [
                'name' => $spiderName,
                'url' => $url,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'create_time' => time(),
            ];

            // 投递队列任务
            Queue::push(RecordSpiderLog::class, $logData);
        }

        return $next($request);
    }

    /**
     * 根据 User-Agent 判断蜘蛛名称
     */
    private function detectSpider(string $userAgent): ?string
    {
        foreach ($this->spiders as $key => $name) {
            if (stripos($userAgent, $key) !== false) {
                return $name;
            }
        }
        return null;
    }
}
