<?php

namespace think\OAuth;

use InvalidArgumentException;
use think\OAuth\contract\DriverInterface;
use think\OAuth\driver\QqWeb;
use think\OAuth\driver\WechatMini;

class OAuth
{
    /**
     * @var DriverInterface
     */
    protected DriverInterface $driver;

    /**
     * 支持的驱动映射（可扩展）
     *
     * @var array<string, string>
     */
    protected array $drivers = [
        'wechat_mini' => WechatMini::class,
        'qq_web' => QqWeb::class,
    ];

    /**
     * 构造函数
     *
     * @param string $name 驱动名称，如 'wechat_mini'
     * @param array $config 驱动所需配置
     * @throws InvalidArgumentException
     */
    public function __construct(string $name, array $config)
    {
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Unsupported OAuth driver: {$name}");
        }

        $driverClass = $this->drivers[$name];

        if (!class_exists($driverClass)) {
            throw new InvalidArgumentException("OAuth driver class not found: {$driverClass}");
        }

        $this->driver = new $driverClass($config);
    }

    /**
     * 获取用户信息（统一入口）
     *
     * @param string $code 授权码
     * @return array ['openid', 'unionid', 'nickname', 'avatar', 'raw']
     * @throws \Exception
     */
    public function getUserInfo(string $code): array
    {
        return $this->driver->getUserInfo($code);
    }
}
