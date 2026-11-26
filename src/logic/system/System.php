<?php
declare (strict_types=1);

namespace app\logic\system;

use happy\admin\libs\BaseLogic;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\helper\Str;

class System extends BaseLogic
{
    /**
     * 获取当前请求的规范 URL（Canonical）
     *
     * 生成规则：
     * 1. 始终输出主站绝对地址：移动端也统一指向 PC 规范地址，避免跨域重复内容；
     * 2. 分页 2~10 页输出自引用地址（?page=N），第 1 页不带分页参数，
     *    10 页以后交由 robots 的 noindex 收敛，canonical 回指列表首页；
     * 3. 路由变量中剔除 page，避免分页参数重复；分页参数按已有查询串正确拼接；
     * 4. 域名取后台配置的 site_domain，规避 CDN 回源协议不一致导致的 http / 相对地址。
     *
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \Throwable
     */
    public function getCanonicalUrl(): string
    {
        $module = $this->request->layer(true);
        $controller = Str::snake($this->request->controller(true, true));
        $action = $this->request->action(true);
        $url = $module . '/' . $controller . '/' . $action;
        $urlInfo = urls($url, (array)$this->request->route());
        // 分页 2~10 页自引用；第 1 页与 10 页以后不输出分页参数
        $pageParam = $this->request->param('page', 1);
        $page = is_scalar($pageParam) ? (int)$pageParam : 1;
        if ($page > 1 && $page <= 10) {
            $urlInfo .= (str_contains($urlInfo, '?') ? '&' : '?') . 'page=' . $page;
        }
        return $this->absoluteUrl($urlInfo);
    }

    /**
     * 补全主站域名，保证 canonical 为 https 绝对地址
     * @param string $path
     * @return string
     * @throws \Throwable
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function absoluteUrl(string $path): string
    {
        $domain = rtrim(trim((string)sys_config('site_domain', '')), '/');
        if ($domain === '') {
            $domain = rtrim((string)$this->request->domain(), '/');
        } elseif (!preg_match('#^https?://#i', $domain)) {
            $domain = 'https://' . $domain;
        }
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        return $domain . $path;
    }
}
