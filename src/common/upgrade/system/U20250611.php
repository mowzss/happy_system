<?php

namespace app\common\upgrade\system;

use app\model\system\SystemConfig;
use app\model\system\SystemConfigGroup;

class U20250611
{
    public function run()
    {
        $this->upConfig();
    }

    private function upConfig()
    {
        $group_id = SystemConfigGroup::where(['title' => '基础设置', 'module' => 'system'])->value('id');
        if (!empty($group_id)) {
            if (empty(SystemConfig::where(['group_id' => $group_id, 'name' => 'editor_default'])->value('id'))) {
                SystemConfig::create([
                    'name' => 'editor_default',
                    'type' => 'select',
                    'title' => '默认富文本编辑器',
                    'group_id' => $group_id,
                    'options' => 'tinymce|tinymce编辑器' . PHP_EOL . 'wangeditor|WangEditor' . PHP_EOL . 'ueditor|百度Ueditor',
                    'help' => '模型设计中默认的富文本编辑器，如模型设计中指定了编辑器，则此设置无效',
                    'value' => '',
                    'extend' => NULL,
                    'list' => '0',
                    'module' => 'system',
                    'status' => '1',
                ]);
            }
        }

    }
}
