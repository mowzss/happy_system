<?php

namespace app\common\middleware\system;

use app\job\system\RecordSpiderLog;
use think\facade\Config;
use think\facade\Queue;
use think\Request;

class SpiderDetectMiddleware
{
    private mixed $spiders;

    public function __construct()
    {
        $this->spiders = Config::get('spiders.list', []);
    }

    public function handle(Request $request, \Closure $next)
    {
        $userAgent = $request->server('HTTP_USER_AGENT', '');
        $ip = $request->ip();

        foreach (array_keys($this->spiders) as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                // 匹配到蜘蛛，记录日志
                $spiderCode = $this->spiders[$pattern];
                $url = $request->url();

                $data = [
                    'name' => $spiderCode,
                    'url' => $url,
                    'ip' => $ip,
                    'module' => $request->layer(),
                    'user_agent' => $userAgent,
                    'create_time' => time()
                ];

                Queue::push(RecordSpiderLog::class, $data);
                break;
            }
        }

        return $next($request);
    }
}
