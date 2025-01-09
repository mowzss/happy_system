<?php
declare (strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemEvent;
use think\App;

class Event extends BaseAdmin
{
    use CrudTrait;

    public function __construct(App $app, SystemEvent $systemEvent)
    {
        parent::__construct($app);
        $this->setParams();
        $this->model = $systemEvent;
    }

    protected function setParams(): void
    {
        $this->tables = [
            //表格字段
            'fields' => [
                [
                    'field' => 'id',
                    'title' => 'ID',
                    'width' => 80,
                    'sort' => true,
                ], [
                    'field' => 'name',
                    'title' => '事件名称',
                ], [
                    'field' => 'info',
                    'title' => '事件描述',
                ], [
                    'field' => 'params_info',
                    'title' => '参数描述',
                ], [
                    'field' => 'list',
                    'title' => '排序',
                    'edit' => 'text'
                ],
                [
                    'field' => 'status',
                    'title' => '状态',
                    'templet' => 'switch'
                ],
            ],
            //表格 表头按钮
            'top_button' => [

            ],

            //表格行按钮
            'right_button' => [

            ],
        ];
        $this->search = [
            'id#=#id', 'name#like#title', 'status#=#status'
        ];
        $this->forms = [
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => '事件名称',
                    'required' => true
                ], [
                    'type' => 'textarea',
                    'name' => 'info',
                    'label' => '事件描述'
                ], [
                    'type' => 'textarea',
                    'name' => 'params_info',
                    'label' => '参数描述'
                ]
            ]
        ];
    }
}
