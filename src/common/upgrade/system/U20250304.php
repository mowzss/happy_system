<?php

namespace app\common\upgrade\system;


use app\model\system\SystemConfig;
use app\service\system\MenuService;
use think\Exception;

class U20250304
{
    /**
     * 升级执行
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        $this->addSystemMenu();
        $this->addSystemConfig();
    }

    /**
     * @return void
     */
    private function addSystemConfig(): void
    {
        $group_id = (new SystemConfig())->where(['title' => '基础设置', 'module' => 'system'])->value('id');
        SystemConfig::create([
            'name' => 'site_domain',
            'type' => 'text',
            'title' => '网站域名',
            'group_id' => $group_id,
            'options' => '',
            'help' => '尽量填写域名，需要带http://或https:// 命令行等模式下部分服务需使用域名',
            'value' => '',
            'extend' => NULL,
            'list' => '0',
            'module' => 'system',
            'status' => '1',
        ]);
    }

    /**
     * 添加系统菜单
     * @return void
     * @throws \think\Exception
     */
    private function addSystemMenu(): void
    {
        (new MenuService())->insertMenusBySlot($this->systemMenu, 'system_ext');
    }

    /**
     * 系统菜单
     * @var array|array[]
     */
    private array $systemMenu = [
        [
            'title' => '计划任务',
            'slot' => '',
            'icon' => 'layui-icon layui-icon-senior',
            'node' => 'system/task/index',
            'params' => '',
            'class' => '1',
            'list' => '0',
            'status' => '1',
        ], [
            'title' => '队列任务',
            'slot' => '',
            'icon' => 'layui-icon layui-icon-senior',
            'node' => 'system/queue/index',
            'params' => '',
            'class' => '1',
            'list' => '0',
            'status' => '1',
        ], [
            'title' => 'Sitemap地图',
            'slot' => '',
            'icon' => 'layui-icon layui-icon-senior',
            'node' => 'system/sitemap/index',
            'params' => '',
            'class' => '1',
            'list' => '0',
            'status' => '1',
        ],
    ];
}
