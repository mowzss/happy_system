<?php

namespace app\common\upgrade\system;

use app\model\system\SystemConfigGroup;
use app\model\system\SystemMenu;
use app\model\system\SystemTasks;

class U20250321
{
    public function run()
    {
        $this->addIndexNowConfigGroup();
        $this->addSystemMenu();
        $this->addTask();
    }

    protected function addTask(): void
    {
        SystemTasks::create([
            'title' => 'indexNow推送',
            'exptime' => mt_rand(0, 59) . ' */4 * * *',
            'task' => '\app\common\task\IndexNow'
        ]);
    }

    protected function addSystemMenu()
    {
        $pid = \app\model\system\SystemMenu::where('slot', 'ext_sys')->value('id');
        $menu_model = SystemMenu::where('pid', $pid)->where('node', 'system/indexNow/index')->findOrEmpty();
        if ($menu_model->isEmpty()) {
            SystemMenu::create([
                'title' => 'IndexNow', 'pid' => $pid, 'params' => '', 'node' => 'system/indexNow/index',
                'icon' => '', 'list' => 100, 'class' => 1
            ]);
        }
    }

    protected function addIndexNowConfigGroup()
    {
        if (SystemConfigGroup::where('module', 'p_index_now')->count() < 1) {
            $group = SystemConfigGroup::create([
                'title' => 'IndexNow推送', 'sys_show' => 0, 'module' => 'p_index_now', 'status' => 1,
            ]);
            $group_id = $group->id;
            (new \app\model\system\SystemConfig)->saveAll([[
                'name' => 'is_open',
                'type' => 'radio',
                'title' => '开启推送',
                'group_id' => $group_id,
                'options' => '1|开启' . PHP_EOL . '0|关闭',
                'help' => '是否开启IndexNow推送',
                'value' => '',
                'extend' => NULL,
                'list' => '1000',
                'module' => 'p_index_now',
                'status' => '1',
            ], [
                'name' => 'open_module',
                'type' => 'xmselect',
                'title' => '开启模块',
                'group_id' => $group_id,
                'options' => '\app\common\fun\SystemModule@getAllModule',
                'help' => '选择开启IndexNow推送模块',
                'value' => '',
                'extend' => NULL,
                'list' => '800',
                'module' => 'p_index_now',
                'status' => '1',
            ], [
                'name' => 'index_key',
                'type' => 'text',
                'title' => '推送秘钥',
                'group_id' => $group_id,
                'options' => '',
                'help' => '设置推送秘钥',
                'value' => md5(time()),
                'extend' => NULL,
                'list' => '900',
                'module' => 'p_index_now',
                'status' => '1',
            ]]);
        }


    }
}
