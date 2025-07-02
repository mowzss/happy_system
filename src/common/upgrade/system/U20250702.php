<?php

namespace app\common\upgrade\system;

use app\model\system\SystemMenu;

class U20250702
{
    public function run()
    {

        $this->addSystemMenu();

    }


    protected function addSystemMenu()
    {
        $pid = \app\model\system\SystemMenu::where('slot', 'system_log')->value('id');
        $menu_model = SystemMenu::where('pid', $pid)->where('node', 'system/spider/index')->findOrEmpty();
        if ($menu_model->isEmpty()) {
            SystemMenu::create([
                'title' => '蜘蛛日志', 'pid' => $pid, 'params' => '', 'node' => 'system/spider/index',
                'icon' => '', 'list' => 100, 'class' => 1
            ]);
        }
    }

}
