<?php

namespace app\common\upgrade\system;

use app\model\system\SystemEvent;

class U1
{
    /**
     * @return void
     */
    public function run(): void
    {
        $this->addEvent();
    }

    /**
     * 新增事件名称
     * @return void
     */
    private function addEvent(): void
    {
        $data = [
            [
                'name' => 'ContentAddBefore',
                'info' => '内容添加前',
                'params_info' => 'POST数据'
            ], [
                'name' => 'ContentAddAfter',
                'info' => '内容添加后',
                'params_info' => 'POST数据,无数据回传'
            ],
            [
                'name' => 'ContentEditBefore',
                'info' => '内容编辑前',
                'params_info' => 'POST数据'
            ], [
                'name' => 'ContentEditAfter',
                'info' => '内容编辑后',
                'params_info' => 'POST数据,无数据回传'
            ],
        ];
        $event = new SystemEvent();
        $event->insertAll($data);
    }
}
