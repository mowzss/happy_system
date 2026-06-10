<?php

namespace app\common\upgrade\system;

use think\Exception;

class U20260610001
{
    /**
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        $this->updateModuleConfig();
    }
    
    /**
     * @return void
     * @throws Exception
     */
    private function updateModuleConfig(): void
    {
        $group_id = (new \app\model\system\SystemConfigGroup)->where(['module' => 'system', 'title' => '基础设置'])->value('id');
        
        if (empty($group_id)) {
            throw new Exception('分组id为空');
        }
        \app\model\system\SystemConfig::create(
            [
                'name' => 'posters_image',
                'type' => 'image',
                'title' => '默认海报图片',
                'group_id' => $group_id,
                'options' => '',
                'help' => '系统全局默认海报图片，海报生成时候如果没有设置图片则使用此图片，优先级低于模块默认海报图片',
                'value' => '',
                'extend' => NULL,
                'list' => '0',
                'module' => 'system',
                'status' => '1',
            ]);
    }
}
