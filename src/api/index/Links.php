<?php
declare (strict_types=1);

namespace app\api\index;

use app\common\controllers\BaseApi;
use app\logic\system\LinksLogic;

/**
 * 友情链接接口（公开）
 *
 * 路由：GET /api.php/index/links/index?cid=1
 * 鉴权：无需登录（继承 BaseApi）
 */
class Links extends BaseApi
{
    /**
     * 获取指定分类的友情链接
     *
     * @return void
     */
    public function index(): void
    {
        $cid = $this->request->param('cid', 1);
        // 仅允许正整数，避免异常类型穿透到查询
        if (!is_numeric($cid) || (int)$cid <= 0) {
            $this->json([], 400, '无效的分类ID');
        }

        $data = LinksLogic::instance()->getLinksByCid((int)$cid);
        $this->json($data, 0);
    }
}
