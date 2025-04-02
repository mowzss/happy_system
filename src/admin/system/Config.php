<?php
declare (strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemConfig;
use app\model\system\SystemConfigGroup;
use app\model\system\SystemModule;
use think\App;

/**
 * 系统参数设计
 */
class Config extends BaseAdmin
{
    use CrudTrait;

    protected SystemConfigGroup $group_model;


    public function __construct(App $app, SystemConfig $model, SystemConfigGroup $group)
    {
        parent::__construct($app);
        $this->model = $model;
        $this->group_model = $group;
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
                    'field' => 'title',
                    'title' => '参数名称',
                    'width' => '200'
                ], [
                    'field' => 'name',
                    'title' => '参数字段',
                    'width' => '200'
                ], [
                    'field' => 'type_name',
                    'title' => '表单类型',
                    'width' => '160'
                ], [
                    'field' => 'group_name',
                    'title' => '所属分组',
                    'sort' => true,
                    'width' => '160'
                ], [
                    'field' => 'list',
                    'title' => '排序',
                    'edit' => 'text',
                    'sort' => true,
                ], [
                    'field' => 'status',
                    'title' => '状态',
                    'width' => '120',
                    'templet' => 'switch'
                ], [
                    'field' => 'create_time',
                    'title' => '添加时间',
                    'width' => '160',
                    'sort' => true,
                ],
            ],
            'top_button' => [
            ],
            'right_button' => [
            ]
        ];
        $this->forms = [
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'title',
                    'label' => '参数名称',
                    'required' => true
                ], [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => '参数字段',
                    'required' => true,
                    'help' => '字母数字组合,不建议使用中文，尽量保证唯一',
                ], [
                    'type' => 'textarea',
                    'name' => 'help',
                    'label' => '输入提示',
                    'help' => '设置项输入提示信息,可为空'
                ], [
                    'type' => 'select',
                    'name' => 'type',
                    'label' => '字段类型',
                    'options' => $this->app->config->get('form'),
                    'required' => true,
                ], [
                    'type' => 'select',
                    'name' => 'group_id',
                    'label' => '所属分组',
                    'options' => $this->getConfigGroupAll(),
                    'required' => true,
                ], [
                    'type' => 'textarea',
                    'name' => 'options',
                    'label' => '数据参数',
                    'help' => '用法'
                ], [
                    'type' => 'text',
                    'name' => 'value',
                    'label' => '默认值',
                    'help' => '增加设置项时设置的值，可在系统设置中修改'
                ]
            ]
        ];
        $this->search = [
            'id#=#id', 'title#like#title', 'name#like#name', 'type#=#type', 'group_id#=#group_id', 'status#=#status', 'create_time#between#create_time', 'update_time#between#update_time'
        ];
    }

    /**
     * 获取分组信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getConfigGroupAll(): array
    {
        $data = SystemConfigGroup::where('status', 1)->field('title,id,module')->select()->toArray();
        $group = [];
        foreach ($data as $key => $item) {
            $module_title = SystemModule::where('dir', $item['module'])->value('title') ?: '未知';
            $group[$item['id']] = $item['title'] . '[' . $module_title . ']';
        }
        return $group;
    }

    /**
     * 获取分组信息
     * @param string|int $group_id
     * @return string
     */
    protected function getConfigGroupInfo(string|int $group_id = 0): string
    {
        $data = SystemConfigGroup::field('title,id,module')->findOrEmpty($group_id);
        if (!$data->isEmpty()) {
            $module_title = SystemModule::where('dir', $data['module'])->value('title') ?: '未知';
            return $data['title'] . '[' . $module_title . ']';
        }
        return '未知';
    }

    /**
     * 列表数据回调
     * @param $data
     * @return void
     */
    protected function _index_list_filter(&$data): void
    {
        $forms = $this->app->config->get('form');
        foreach ($data['data'] as &$item) {

            $item['group_name'] = $this->getConfigGroupInfo($item['group_id']);
            $item['type_name'] = $forms[$item['type']] ?? '未定义类型';
        }
    }

    /**
     * 保存前置处理
     * @param $data
     * @return void
     */
    protected function _save_filter(&$data): void
    {
        if (!empty($data['group_id'])) {
            $data['module'] = $this->group_model->where(['id' => $data['group_id']])->value('module');
        }
    }
}
