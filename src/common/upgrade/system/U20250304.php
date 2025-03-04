<?php

namespace app\common\upgrade\system;


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
