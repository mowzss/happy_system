<?php
declare (strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemModule;
use think\App;

/**
 * 系统模块管理
 */
class Module extends BaseAdmin
{
    use CrudTrait;

    public function __construct(App $app, SystemModule $module)
    {
        parent::__construct($app);
        $this->model = $module;
        $this->setParams();
    }

    /**
     * @return void
     */
    protected function setParams(): void
    {
        $this->tables = ['fields' => [
            [
                'field' => 'id',
                'title' => 'ID',
                'width' => 80,
                'sort' => true,
            ],
            [
                'field' => 'title',
                'title' => '模块名称',
                'align' => 'content',
                'width' => 360,
            ], [
                'field' => 'dir',
                'title' => '模块目录',
            ], [
                'field' => 'type_name',
                'title' => '类型',
            ], [
                'field' => 'status',
                'title' => '状态',
                'templet' => 'switch'
            ], [
                'field' => 'create_time',
                'title' => '创建时间',
            ],
        ]];
        $this->forms = ['fields' => [
            [
                'type' => 'text',
                'name' => 'title',
                'label' => '模块名称',
            ], [
                'type' => 'text',
                'label' => 'dir',
                'title' => '模块目录',
            ], [
                'type' => 'radio',
                'label' => 'type',
                'title' => '类型',
                'options' => [
                    1 => '模块',
                    2 => '插件'
                ]
            ],
        ]];
        $this->search = ['id#=#id', 'title#=#title', 'dir#=#module', 'status#=#status', 'create_time#between#create_time', 'update_time#between#update_time'];
    }
}
