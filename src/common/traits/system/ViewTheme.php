<?php
declare(strict_types=1);

namespace app\common\traits\system;

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Env;
use think\facade\Request;

trait ViewTheme
{
    /**
     * 设置模板路径
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function setView(): void
    {
        $this->app->config->set(['view_dir_name' => $this->getPath()], 'view');
    }

    /**
     * 获取模板根路径
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function getPath(): string
    {
        // 根据控制器层和设备类型获取模板风格
        $theme = $this->getTheme();

        // 构建完整的模板路径
        return 'view' . DIRECTORY_SEPARATOR . $this->getStylePath() . DIRECTORY_SEPARATOR . $theme;
    }

    /**
     * 获取模板风格目录
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function getTheme(): string
    {
        // 获取当前请求的设备类型
        $isMobile = Request::instance()->isMobile();
        $controllerLayer = Env::get('CONTROLLER_LAYER');

        // 使用与 getStylePath 相同的逻辑判断控制器层
        if ($controllerLayer === 'admin') {
            $configKey = 'admin_style';
        } elseif ($this->request->layer(true) === 'user') {
            $configKey = $isMobile ? 'user_wap_style' : 'user_pc_style';
        } else {
            $configKey = $isMobile ? 'home_wap_style' : 'home_pc_style';
        }

        try {// 从配置中读取模板风格，如果没有设置则使用默认值
            $theme = sys_config($configKey, $this->getDefaultTheme($configKey));
        } catch (DataNotFoundException|DbException $e) {
            $theme = $this->getDefaultTheme($configKey);
        }

        // 如果是 WAP 风格，设置缓存前缀
        if (in_array($configKey, ['user_wap_style', 'home_wap_style'])) {
            $this->app->config->set(['cache_prefix' => 'wap_'], 'view');
        } else {
            $this->app->config->set(['cache_prefix' => 'pc_'], 'view');
        }

        return $theme;
    }

    /**
     * 获取默认的模板风格
     * @param string $configKey
     * @return string
     */
    protected function getDefaultTheme(string $configKey): string
    {
        return match ($configKey) {
            'admin_style' => 'default',
            'user_wap_style' => 'wap_default',
            'user_pc_style' => 'default',
            'home_wap_style' => 'wap_default',
            'home_pc_style' => 'default',
            default => 'default'
        };
    }

    /**
     * 获取模板风格路径
     * @return string
     */
    protected function getStylePath(): string
    {
        // 使用现有逻辑判断控制器层
        if (Env::get('CONTROLLER_LAYER') == 'admin') {
            return 'admin_style';
        } else {
            if ($this->request->layer(true) == 'user') {
                return 'user_style';
            } else {
                return 'home_style';
            }
        }
    }
}
