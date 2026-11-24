<?php
declare (strict_types=1);

namespace app\api\index;

use app\common\controllers\BaseApi;
use app\logic\system\NavLogic;

/**
 * 站点导航接口（公开）
 *
 * 路由：GET /api.php/index/nav/index?dir=pc
 * 鉴权：无需登录（继承 BaseApi）
 */
class Nav extends BaseApi
{
    /**
     * 允许的导航分组，与 config/extra/nav.php 保持一致。
     * 采用白名单校验，避免任意 dir 穿透查询。
     */
    private const ALLOW_DIRS = [
        'pc', 'wap', 'wap_footer', 'pc_user', 'wap_user', 'wap_user_footer',
    ];

    /**
     * 获取指定分组的导航
     *
     * @return void
     */
    public function index(): void
    {
        $dir = (string)$this->request->param('dir', 'pc');
        if (!in_array($dir, self::ALLOW_DIRS, true)) {
            $this->json([], 400, '无效的导航分组');
        }

        $data = NavLogic::instance()->getNavByDir($dir);
        $this->json($data, 0);
    }
}
