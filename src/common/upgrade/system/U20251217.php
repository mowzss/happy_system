<?php

namespace app\common\upgrade\system;

use app\model\system\SystemConfig;
use app\model\system\SystemConfigGroup;

class U20251217
{
    /**
     * @return void
     */
    public function run(): void
    {
        $this->upConfig();
    }

    /**
     * 增加配置项
     * @return void
     */
    private function upConfig(): void
    {
        $group_id = SystemConfigGroup::where(['title' => '前端资源', 'module' => 'system'])->value('id');
        if (!empty($group_id)) {
            if (empty(SystemConfig::where(['group_id' => $group_id, 'name' => 'static_version'])->value('id'))) {
                SystemConfig::create([
                    'name' => 'static_version',
                    'type' => 'text',
                    'title' => '前端资源版本号',
                    'group_id' => $group_id,
                    'options' => '',
                    'help' => '适应前端资源缓存，默认上传云存储资源后自动设置版本号',
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
