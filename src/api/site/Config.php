<?php
declare (strict_types=1);


namespace app\api\site;

use app\logic\system\ConfigLogic;
use happy\admin\libs\Controller;

/**
 * 站点公开配置接口
 *
 * 面向 Vue / 前后端分离的前端提供可安全公开的站点配置。
 * 通过 ConfigLogic::getPublicConfig() 的白名单输出，禁止暴露任何密钥类配置。
 *
 * 路由：GET /api.php/site/config
 * 鉴权：无需登录（已在 JWTAuthDefaultScene 中放行 site.Config）
 */
class Config extends Controller
{
    /**
     * 获取公开配置
     *
     * @return void
     * @throws \Throwable
     */
    public function index(): void
    {
        $data = ConfigLogic::instance()->getPublicConfig();
        // 显式声明 code=0 表示成功，避免两站 vendor json() 默认值不同（0/1）导致语义漂移
        $this->json($data, 0);
    }
}
