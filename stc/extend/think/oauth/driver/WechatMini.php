<?php

namespace think\oauth\driver;

use think\oauth\contract\DriverInterface;
use think\oauth\Http;

abstract class WechatMini implements DriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        // 必填参数校验
        if (empty($config['appid']) || empty($config['secret'])) {
            throw new \InvalidArgumentException('WechatMini driver requires "appid" and "secret".');
        }

        $this->config = $config;
    }

    /**
     * 通过 code 获取用户信息（实际返回 openid/session_key）
     *
     * @param string $code 小程序登录时 wx.login() 获取的临时 code
     * @return array
     * @throws \Exception
     */
    public function getUserInfo(string $code): array
    {
        $url = 'https://api.weixin.qq.com/sns/jscode2session';

        $options = [
            'query' => [
                'appid' => $this->config['appid'],
                'secret' => $this->config['secret'],
                'js_code' => $code,
                'grant_type' => $this->config['grant_type'] ?? 'authorization_code',
            ],
            'timeout' => $this->config['timeout'] ?? 10,
        ];

        try {
            $result = Http::get($url, $options);
            $data = json_decode($result['response'], true);
        } catch (\Exception $e) {
            throw new \Exception('微信小程序登录接口请求失败: ' . $e->getMessage(), 0, $e);
        }

        // 微信错误码处理
        if (isset($data['errcode']) && $data['errcode'] !== 0) {
            $errmsg = $data['errmsg'] ?? 'unknown error';
            throw new \Exception("微信返回错误 [{$data['errcode']}]: {$errmsg}");
        }

        if (!isset($data['openid']) || !isset($data['session_key'])) {
            throw new \Exception('微信响应缺少 openid 或 session_key');
        }

        // 构造统一用户数据格式
        return [
            'openid' => $data['openid'],
            'nickname' => '', // 小程序无法直接获取昵称，需前端提供或调用 getUserProfile
            'avatar' => '', // 同上
            'unionid' => $data['unionid'] ?? '',
            'raw' => $data, // 原始响应（含 session_key，注意保密！）
        ];
    }
}
