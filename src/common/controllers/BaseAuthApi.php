<?php
declare (strict_types=1);


namespace app\common\controllers;

use happy\admin\libs\Controller;
use think\App;
use yzh52521\Jwt\JWT;
use yzh52521\Jwt\Util\JWTUtil;

/**
 * 需鉴权的 API 基类。
 *
 * 鉴权约定（新的分层模型）：
 * - 继承本类 = 需要登录鉴权；继承 BaseApi = 公开接口，无需鉴权。
 * - 鉴权在本基类完成，不依赖全局 JWT 中间件，因此不存在白名单膨胀问题。
 * - 校验链：取 token → verifyTokenAndScene（含签名/有效期/场景/黑名单）→ 取 uid。
 *   不可直接用 JWTUtil::getParserData()：它只解码不验签，存在伪造风险。
 */
class BaseAuthApi extends Controller
{
    protected int $uid;

    protected JWT $jwt;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->jwt = new JWT();
    }

    protected function initialize(): void
    {
        $this->app->event->trigger('AuthApiControllerInit');

        try {
            $token = JWTUtil::getToken($this->request);
            if (!$token || !is_string($token)) {
                $this->json([], 401, '未授权');
            }
            // 完整校验：签名、有效期、场景、黑名单
            $this->jwt->verifyTokenAndScene('default', $token);
            $claims = $this->jwt->getClaimsByToken($token);
            $this->uid = (int)($claims['id'] ?? 0);
            if (!$this->uid) {
                $this->json([], 401, '未授权');
            }
        } catch (\Throwable $e) {
            $this->json([], 401, '未授权');
        }

        parent::initialize();
    }
}
