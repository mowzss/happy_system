<?php

namespace app\common\upgrade\system;

use app\model\system\SystemConfig;
use app\model\system\SystemConfigGroup;

class U20251211
{
    /**
     * @return void
     */
    public function run(): void
    {
        $this->installConfigInfo();
    }

    /**
     * 配置信息
     * @return void
     */
    private function installConfigInfo(): void
    {
        $group_id = SystemConfigGroup::where(['title' => '前端资源', 'module' => 'system'])->value('id');
        if (empty($group_id)) {
            $group_id = SystemConfigGroup::create([
                'title' => '前端资源',
                'module' => 'system',
                'sys_show' => 1,
                'status' => 1,
            ])->id;
        }
        if (!empty($group_id)) {
            SystemConfig::saveAll([
                [
                    'name' => 'static_upload',
                    'type' => 'radio',
                    'title' => '前端静态文件存储位置',
                    'group_id' => $group_id,
                    'options' => 'local|本地' . PHP_EOL . 'oss|阿里云oss' . PHP_EOL . 'qiniu|七牛云',
                    'help' => '前端静态文件存储位置，如存储至阿里云oss则需要在系统设置-存储配置中配置oss信息。 配置后可执行php think oss:upload-static 初始化上传静态文件数据',
                    'value' => 'local',
                    'extend' => NULL,
                    'list' => '0',
                    'module' => 'system',
                    'status' => '1',
                ], [
                    'name' => 'static_prefix',
                    'type' => 'text',
                    'title' => '云存储路径前缀',
                    'group_id' => $group_id,
                    'options' => '',
                    'help' => '上传至远程的文件路径前缀',
                    'value' => 'static',
                    'extend' => NULL,
                    'list' => '0',
                    'module' => 'system',
                    'status' => '1',
                ], [
                    'name' => 'static_local_path',
                    'type' => 'text',
                    'title' => '本地文件路径',
                    'group_id' => $group_id,
                    'options' => '',
                    'help' => '静态文件本地位置 无需public',
                    'value' => 'static',
                    'extend' => NULL,
                    'list' => '0',
                    'module' => 'system',
                    'status' => '1',
                ],
            ]);

        }

    }
}
