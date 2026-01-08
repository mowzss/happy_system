<?php

namespace think\oauth\driver;

use think\oauth\contract\DriverInterface;
use think\oauth\Http;

class QqWeb implements DriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        if (empty($config['appid']) || empty($config['appkey']) || empty($config['redirect_uri'])) {
            throw new \InvalidArgumentException('QqWeb driver requires "appid", "appkey", and "redirect_uri".');
        }

        $this->config = $config;
    }

    public function getUserInfo(string $code): array
    {
        // Step 1: code → access_token
        $tokenUrl = 'https://graph.qq.com/oauth2.0/token';
        $tokenOptions = [
            'query' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['appid'],
                'client_secret' => $this->config['appkey'],
                'code' => $code,
                'redirect_uri' => urlencode($this->config['redirect_uri']),
            ],
            'timeout' => $this->config['timeout'] ?? 10,
        ];

        try {
            $result = Http::get($tokenUrl, $tokenOptions);
            parse_str($result['response'], $tokenData);
        } catch (\Exception $e) {
            throw new \Exception('获取 QQ access_token 失败: ' . $e->getMessage(), 0, $e);
        }

        if (!isset($tokenData['access_token'])) {
            throw new \Exception('QQ 返回无效 access_token 响应: ' . $result['response']);
        }

        $accessToken = $tokenData['access_token'];

        // Step 2: access_token → openid + unionid（关键：加 unionid=1）
        $openIdUrl = 'https://graph.qq.com/oauth2.0/me?' . http_build_query([
                'access_token' => $accessToken,
                'unionid' => 1, // ← 必须显式请求 unionid
            ]);

        try {
            $result = Http::get($openIdUrl, ['timeout' => $this->config['timeout'] ?? 10]);
            $content = trim($result['response']);
        } catch (\Exception $e) {
            throw new \Exception('获取 QQ openid/unionid 失败: ' . $e->getMessage(), 0, $e);
        }

        // 解析 callback({...}) 格式
        if (preg_match('/callback\(\s*(\{.*\})\s*\)/', $content, $matches)) {
            $jsonStr = $matches[1];
        } elseif (preg_match('/^\{.*\}$/', $content)) {
            $jsonStr = $content;
        } else {
            throw new \Exception('无法解析 QQ openid 响应: ' . $content);
        }

        $openIdData = json_decode($jsonStr, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON 解析失败: ' . json_last_error_msg());
        }

        if (!isset($openIdData['openid'])) {
            throw new \Exception('QQ 未返回 openid: ' . $jsonStr);
        }

        $openid = $openIdData['openid'];
        $unionid = $openIdData['unionid'] ?? ''; // 可能为空（未满足条件）

        // Step 3: 获取用户基本信息
        $userInfoUrl = 'https://graph.qq.com/user/get_user_info';
        $userOptions = [
            'query' => [
                'access_token' => $accessToken,
                'oauth_consumer_key' => $this->config['appid'],
                'openid' => $openid,
            ],
            'timeout' => $this->config['timeout'] ?? 10,
        ];

        try {
            $result = Http::get($userInfoUrl, $userOptions);
            $userInfo = json_decode($result['response'], true);
        } catch (\Exception $e) {
            throw new \Exception('获取 QQ 用户信息失败: ' . $e->getMessage(), 0, $e);
        }

        if (($userInfo['ret'] ?? -1) != 0) {
            throw new \Exception('QQ 用户信息接口错误: ' . ($userInfo['msg'] ?? 'unknown'));
        }

        return [
            'openid' => $openid,
            'unionid' => $unionid, // ← 新增 unionid
            'nickname' => $userInfo['nickname'] ?? '',
            'avatar' => $userInfo['figureurl_qq_2'] ?? $userInfo['figureurl_qq_1'] ?? $userInfo['figureurl'] ?? '',
            'raw' => array_merge($userInfo, ['openid_raw' => $openid, 'unionid_raw' => $unionid]),
        ];
    }
}
