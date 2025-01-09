<?php
declare (strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemEvent;
use app\model\system\SystemEventListen;
use think\App;

class EventListen extends BaseAdmin
{
    use CrudTrait;

    protected SystemEvent $systemEventModel;

    public function __construct(App $app, SystemEventListen $systemEventListen, SystemEvent $systemEvent)
    {
        parent::__construct($app);
        $this->model = $systemEventListen;
        $this->systemEventModel = $systemEvent;
        $this->setParams();
    }

    protected function setParams(): void
    {
        $this->tables = [
            'fields' => [
                [
                    'field' => 'id',
                    'title' => 'ID',
                    'width' => 80,
                    'sort' => true,
                ],
                [
                    'field' => 'event_key',
                    'title' => '事件名称',
                ], [
                    'field' => 'event_class',
                    'title' => '事件监听类',
                ], [
                    'field' => 'info',
                    'title' => '描述',
                ], [
                    'field' => 'list',
                    'title' => '排序',
                    'edit' => 'text',
                    'sort' => true,
                ], [
                    'field' => 'status',
                    'title' => '状态',
                    'templet' => 'switch'
                ]
            ]
        ];
        $this->forms = [
            'fields' => [
                [
                    'type' => 'select',
                    'name' => 'event_key',
                    'label' => '事件名称',
                    'options' => $this->systemEventModel->where('status', 1)->column('name', 'name'),
                    'required' => true
                ], [
                    'type' => 'text',
                    'name' => 'event_class',
                    'label' => '事件监听类',
                    'required' => true
                ], [
                    'type' => 'textarea',
                    'name' => 'info',
                    'label' => '描述',
                ],
            ]
        ];
        $this->search = [
            'id#=#id', 'event_key#=#event_key', 'event_class#like#event_class', 'status#=#status'
        ];
    }
}
