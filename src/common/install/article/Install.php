<?php

namespace app\common\install\article;

use app\model\system\SystemConfigGroup;
use app\service\system\MenuService;

/**
 * 模块安装
 * 用于数据库插入记录
 * 建表操作建议使用sql文件 模块安装默认使用install.sql文件
 * 升级操作在 src/common/upgrade目录
 *
 */
class Install
{

    public function run(): void
    {
        //插入模块管理菜单
        $this->installSystemMenu();
        //插入模块配置信息
        $this->installSysConfig();
    }

    private function installSysConfig(): void
    {
        $config_group = (new SystemConfigGroup())->insert(['title' => '文章模块设置', 'sys_show' => '0', 'module' => 'article', 'status' => '1'], true);
        (new \app\model\system\SystemConfig)->insertAll([
                ['name' => 'name', 'type' => 'text', 'title' => '模块名称', 'group_id' => $config_group, 'options' => '', 'help' => '当前模块首页的SEO信息', 'value' => '文章模块', 'extend' => NULL, 'list' => '0', 'module' => 'article', 'status' => '1',],
                ['name' => 'seo_title', 'type' => 'text', 'title' => 'SEO标题', 'group_id' => $config_group, 'options' => '', 'help' => '当前模块首页的SEO信息', 'value' => '文章模块', 'extend' => NULL, 'list' => '0', 'module' => 'article', 'status' => '1',],
                ['name' => 'seo_keywords', 'type' => 'text', 'title' => 'SEO关键词', 'group_id' => $config_group, 'options' => '', 'help' => '当前模块首页的SEO信息', 'value' => '文章模块', 'extend' => NULL, 'list' => '0', 'module' => 'article', 'status' => '1',],
                ['name' => 'seo_description', 'type' => 'textarea', 'title' => 'SEO描述', 'group_id' => $config_group, 'options' => '', 'help' => '当前模块首页的SEO信息', 'value' => '文章模块', 'extend' => NULL, 'list' => '0', 'module' => 'article', 'status' => '1',]
            ]
        );
    }

    private function installSystemMenu(): void
    {
        $menuService = new MenuService();
        $menuService->insertMenus($this->menu);
    }

    /**
     * 系统菜单插入位置
     * @var string
     */
    private string $slot = "content";
    /**
     * 菜单数据
     * @var array|array[]
     */
    private array $menu = [
        [
            'title' => '文章模块',
            'slot' => 'content_article',
            'icon' => 'layui-icon layui-icon-app',
            'node' => '',
            'params' => '',
            'class' => '1',
            'list' => '0',
            'status' => '1',
            'sub' => [
                ['title' => '模块设置', 'icon' => '', 'node' => 'article/setting/index', 'params' => '', 'class' => '1', 'list' => '10000', 'status' => '1'],
                ['title' => '内容列表', 'icon' => '', 'node' => 'article/content/index', 'params' => '', 'class' => '1', 'list' => '9000', 'status' => '1'],
                ['title' => '分类管理', 'icon' => '', 'node' => 'article/column/index', 'params' => '', 'class' => '1', 'list' => '8000', 'status' => '1'],
                ['title' => 'TAG标签', 'icon' => '', 'node' => 'article/tag/index', 'params' => '', 'class' => '1', 'list' => '7000', 'status' => '1'],
                ['title' => '模型设计', 'icon' => '', 'node' => 'article/model/index', 'params' => '', 'class' => '1', 'list' => '6000', 'status' => '1'],
            ],
        ],

    ];
}
