<?php
declare (strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemConfigGroup;
use app\model\system\SystemModule;
use think\App;

/**
 * 设置组管理
 */
class ConfigGroup extends BaseAdmin
{
    use CrudTrait;

    protected SystemModule $module;

    public function __construct(App $app, SystemConfigGroup $model, SystemModule $module)
    {
        parent::__construct($app);
        $this->model = $model;
        $this->module = $module;
        $this->setParams();
    }

    /**
     * 处理列表数据
     * @param $data
     * @return void
     */
    protected function _index_list_filter(&$data): void
    {
        foreach ($data['data'] as &$v) {
            $v['module_name'] = $this->module->where('dir', $v['module'])->value('title');
        }
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
                    'field' => 'title',
                    'title' => '名称',
                ], [
                    'field' => 'module_name',
                    'title' => '所属模块',
                ], [
                    'field' => 'sys_show',
                    'title' => '系统设置显示',
                    'width' => 120,
                    'templet' => 'switch',
                    'switch' => [
                        'name' => '显示|隐藏'
                    ]
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
                [
                    'event' => '',
                    'type' => 'data-open',
                    'url' => urls('system/config/index', ['group_id' => '__id__']),
                    'name' => '管理设置字段',
                    'class' => '',//默认包含 layui-btn layui-btn-xs
                ],
                ['event' => 'edit'],
                ['event' => 'del'],
            ],
        ];
        $this->search = [
            'id#=#id', 'title#=#title', 'module#=#module', 'status#=#status'
        ];
        $this->forms = [
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'title',
                    'label' => '名称',
                    'required' => true
                ], [
                    'type' => 'radio',
                    'name' => 'sys_show',
                    'label' => '系统设置显示',
                    'options' => [0 => '不显示', 1 => '显示'],
                    'required' => true
                ], [
                    'type' => 'select',
                    'name' => 'module',
                    'label' => '归属模块',
                    'options' => SystemModule::where('status', 1)->column('title', 'dir'),
                    'required' => true
                ]
            ]
        ];
    }
}
