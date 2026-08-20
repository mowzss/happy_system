<?php

namespace app\middleware\system;

use think\App;
use think\response\Redirect;
use think\db\exception\DbException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

class MobileBrowseMiddleware
{
    /**
     * 当前 App 对象
     * @var \think\App
     */
    protected App $app;
    
    /**
     * Construct
     * @param \think\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }
    
    /**
     * @param $request
     * @param \Closure $next
     * @return mixed|Redirect
     * @throws \Throwable
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function handle($request, \Closure $next): mixed
    {
        if (!empty((int)sys_config('is_wap_domain', 0)) && !empty(sys_config('is_wap_domain_dump', 0))) {
            if ($this->app->request->isMobile() && format_url(sys_config('site_wap_domain'), 'host') !== $this->app->request->host()) {
                return redirect(sys_config('site_wap_domain') . $this->app->request->url(), 301);
            }
            
            if (!$this->app->request->isMobile() && format_url(sys_config('site_domain'), 'host') !== $this->app->request->host()) {
                return redirect(sys_config('site_domain') . $this->app->request->url(), 301);
            }
            
        }
        
        return $next($request);
    }
}
