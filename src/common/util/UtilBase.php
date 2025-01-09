<?php
declare (strict_types=1);

namespace app\common\util;

use mowzs\lib\Helper;
use think\App;
use think\Container;

class UtilBase
{
    /**
     * @var App
     */
    protected App $app;

    // 静态存储类的实例
    protected static $instances = [];

    // 构造函数用于依赖注入
    public function __construct(App $app = null)
    {
        $this->app = $app ?: app();
    }

    /**
     * 获取当前时间戳
     * @return int
     */
    public function getCurrentTimestamp()
    {
        return time();
    }

    /**
     * 格式化日期时间
     * @param $timestamp
     * @param $format
     * @return false|string
     */
    public function formatDateTime($timestamp = null, $format = 'Y-m-d H:i:s')
    {
        $timestamp = $timestamp ?? $this->getCurrentTimestamp();
        return date($format, $timestamp);
    }

    /**
     * 静态实例对象
     * @param array $var 实例参数
     * @param boolean $new 创建新实例
     * @return Helper
     */
    public static function instance(array $var = [], bool $new = false): static
    {
        return Container::getInstance()->make(static::class, $var, $new);
    }
}
