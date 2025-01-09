<?php

namespace app\admin\user;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\user\UserGroup;
use think\App;

class Group extends BaseAdmin
{
    use CrudTrait;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new UserGroup();
        $this->setParams();
    }

    protected function setParams(): void
    {
        // 定义表格字段
        $this->tables['fields'] = [
            [
                'field' => 'id',
                'title' => 'ID',
                'width' => 80,
                'sort' => true,
            ],
            [
                'field' => 'name',
                'title' => '用户组名称',
                'width' => 200,
            ],
            [
                'field' => 'desc',
                'title' => '用户组描述',
            ],
            [
                'field' => 'status',
                'title' => '状态',
                'templet' => 'switch'
            ],
            [
                'field' => 'create_time',
                'title' => '创建时间',
                'width' => 200,
                'sort' => true,
            ],
            [
                'field' => 'update_time',
                'title' => '更新时间',
                'width' => 200,
                'sort' => true,
            ],
        ];

        // 定义表单字段
        $this->forms['fields'] = [
            [
                'type' => 'text',
                'name' => 'name',
                'label' => '用户组名称',
            ],
            [
                'type' => 'textarea',
                'name' => 'desc',
                'label' => '用户组描述',
            ], [
                'type' => 'text',
                'name' => 'upgrade_points',
                'label' => '升级所需积分',
            ], [
                'type' => 'text',
                'name' => 'upgrade_day',
                'label' => '升级有效期(天)',
            ],
        ];

        // 定义搜索条件
        $this->search = [
            'id#=#id',
            'name#like#name',
            'status#=#status',
            'create_time#between#create_time',
            'update_time#between#update_time',
        ];
    }

}
