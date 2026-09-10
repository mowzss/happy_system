<?php

namespace app\common\upgrade\system;

use app\model\system\SystemConfig;
use app\model\system\SystemConfigGroup;

class U2026091001
{
    public function run(): void
    {
        $this->updateModuleConfig();
    }
    
    /**
     * 更新模块配置
     * @return void
     */
    private function updateModuleConfig(): void
    {
        $group_id = SystemConfigGroup::where([
            'title' => '手机版设置',
            'module' => 'system',
        ])->value('id');
        
        SystemConfig::saveAll([
            [
                'name' => 'is_wap_dump_type',
                'type' => 'radio',
                'title' => '重定向方式',
                'group_id' => $group_id,
                'options' => '1|php' . PHP_EOL . '0|JavaScript',
                'help' => '开启重定向后 重定向方案 js需自行在前端实现',
                'value' => '0',
                'extend' => null,
                'list' => '0',
                'module' => 'system',
                'status' => '1',
            ],
        ]);
    }
    
    
}
