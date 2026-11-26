<?php
declare (strict_types=1);

namespace app\logic\system;

use happy\admin\libs\BaseLogic;
use think\helper\Str;

class System extends BaseLogic
{
    /**
     * 获取当前请求的 URL 信息
     *
     * - pc_url：主站域名 + 当前请求地址（含查询串），用于 PC / 移动端互跳
     * - mobile_url：移动端域名 + 当前请求地址（未配置移动域名时回退主站域名）
     * - canonical_url：主站绝对规范地址，按路由重建并剔除无关查询参数，
     *   分页 2~10 页自引用（?page=N），第 1 页与 10 页以后不带分页参数
     *
     * @return array{pc_url: string, mobile_url: string, canonical_url: string}
     */
    public function getUrlInfo(): array
    {
        $domain = $this->domain('site_domain') ?: rtrim((string)$this->request->domain(), '/');
        $wapDomain = $this->domain('site_wap_domain') ?: $domain;
        $requestUrl = $this->normalizePath($this->request->url());

        return [
            'pc_url' => $domain . $requestUrl,
            'mobile_url' => $wapDomain . $requestUrl,
            'canonical_url' => $domain . $this->canonicalPath(),
        ];
    }

    /**
     * 生成规范路径：按路由变量还原简化地址，仅保留业务需要的分页参数
     *
     * @return string
     */
    private function canonicalPath(): string
    {
        $url = $this->request->layer(true) . '/'
            . Str::snake($this->request->controller(true, true)) . '/'
            . $this->request->action(true);

        // 路由变量只包含路径参数（如 id），不含 GET；分页参数统一走查询串，避免路径分页重复
        $vars = (array)$this->request->route();
        unset($vars['page']);

        $path = urls($url, $vars, true, false);

        $page = (int)$this->request->param('page', 1);
        if ($page > 1 && $page <= 10) {
            $path .= (str_contains($path, '?') ? '&' : '?') . 'page=' . $page;
        }

        return $this->normalizePath($path);
    }

    /**
     * 读取并规范化后台配置的域名
     *
     * @param string $configKey
     * @return string
     */
    private function domain(string $configKey): string
    {
        $domain = rtrim(trim((string)sys_config($configKey, '')), '/');

        if ($domain === '') {
            return '';
        }

        return preg_match('#^https?://#i', $domain) ? $domain : 'https://' . $domain;
    }

    /**
     * 保证地址以单个斜杠开头
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}
