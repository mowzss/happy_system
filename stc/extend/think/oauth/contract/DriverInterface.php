<?php

namespace think\OAuth\contract;

interface DriverInterface
{
    /**
     * 通过授权凭证获取标准化用户信息
     *
     * @param string $code
     * @return array 标准化用户数据 ['openid', 'nickname', 'avatar', 'raw']
     */
    public function getUserInfo(string $code): array;
}
