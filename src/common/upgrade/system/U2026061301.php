<?php

namespace app\common\upgrade\system;

use app\model\system\SystemConfig;
use app\model\system\SystemConfigGroup;

class U2026061301
{
    public function run(): void
    {
        $this->updateModuleConfig();
    }
    
    /**
     * @return void
     */
    private function updateModuleConfig(): void
    {
        $group_model = new SystemConfigGroup();
        $config_group_data = [
            'title' => '手机版设置',
            'sys_show' => 1,
            'module' => 'system',
            'status' => 1,
        ];
        $group_model = $group_model->save($config_group_data);
        
        SystemConfig::saveAll([
            [
                'name' => 'site_wap_domain',
                'type' => 'text',
                'title' => '手机版域名',
                'group_id' => $group_model->id,
                'options' => '',
                'help' => '填写域名，需要包含http://或https:// 无需/结尾',
                'value' => '',
                'extend' => NULL,
                'list' => '0',
                'module' => 'system',
                'status' => '1',
            ], [
                'name' => 'is_wap_domain',
                'type' => 'radio',
                'title' => '是否使用手机版域名',
                'group_id' => $group_model->id,
                'options' => '0|否' . PHP_EOL . '1|是',
                'help' => '是否使用手机版独立域名访问',
                'value' => '0',
                'extend' => NULL,
                'list' => '0',
                'module' => 'system',
                'status' => '1',
            ], [
                'name' => 'is_wap_domain_dump',
                'type' => 'radio',
                'title' => '手机版域名是否重定向',
                'group_id' => $group_model->id,
                'options' => '0|否' . PHP_EOL . '1|是',
                'help' => '开启重定向后，pc端访问手机版域名时，将自动重定向到pc端域名',
                'value' => '0',
                'extend' => NULL,
                'list' => '0',
                'module' => 'system',
                'status' => '1',
            ],
        ]);
    }
    
    
}
