<?php

namespace app\common\middleware\api;

use think\Request;
use yzh52521\Jwt\Exception\TokenValidException;
use yzh52521\Jwt\JWT;
use yzh52521\Jwt\Util\JWTUtil;

class JWTAuthDefaultSceneMiddleware
{
    /**
     * Construct
     * @param JWT $jwt
     */
    public function __construct(protected JWT $jwt)
    {
    }

    public function handle(Request $request, $next)
    {

        $token = JWTUtil::getToken($request);
        if ($token !== false && $this->jwt->verifyTokenAndScene('default', $token)) {
            $jwtConfig = $this->jwt->getJwtSceneConfig();
            $jwtConfig['user_model'] && $request->user = $this->jwt->getUser();
            return $next($request);
        }
        throw new TokenValidException('Token authentication does not pass', 400);
    }
}
