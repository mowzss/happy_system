<?php

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemTasks;
use think\App;

class Task extends BaseAdmin
{
    use CrudTrait;

    /**
     * 页面标题
     * @var string
     */
    protected string $title = '计划任务';
    /**
     * 默认排序
     * @var array
     */
    protected array $default_order = [
        'list' => 'desc'
    ];

    public function __construct(SystemTasks $model, App $app)
    {
        parent::__construct($app);
        $this->model = $model;
        $this->setParams();
    }

    /**
     * @return void
     */
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
                    'title' => '任务名称',
                    'align' => 'content'
                ], [
                    'field' => 'exptime',
                    'title' => 'cron表达式',
                ], [
                    'field' => 'exptime_info',
                    'title' => '运行周期',
                ], [
                    'field' => 'task',
                    'title' => '任务类/命令',
                    'edit' => 'text',
                ], [
                    'field' => 'last_time',
                    'title' => '最后执行时间',
                ], [
                    'field' => 'next_time',
                    'title' => '下次执行时间',
                ],
                [
                    'field' => 'list',
                    'title' => '排序',
                    'edit' => 'text',
                    'sort' => true,
                ], [
                    'field' => 'status',
                    'title' => '状态',
                    'templet' => 'switch'
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
                    'label' => '菜单名称',
                    'required' => true
                ], [
                    'type' => 'select',
                    'name' => 'pid',
                    'label' => '父级栏目',
                    'options' => $this->model->getMenuForm(),
                    'required' => true
                ], [
                    'type' => 'icon',
                    'name' => 'icon',
                    'label' => '图标',
                ], [
                    'type' => 'text',
                    'name' => 'node',
                    'label' => '节点',
                ], [
                    'type' => 'text',
                    'name' => 'params',
                    'label' => '参数',
                    'help' => '链接附加参数 示例: a=xx&b=Xxs'
                ], [
                    'type' => 'radio',
                    'name' => 'class',
                    'label' => '链接类型',
                    'options' => [
                        '1' => '节点',
                        '2' => '链接'
                    ],
                ]
            ]
        ];
        $this->search = [
            'id#=#id', 'title#like#name', 'type#=#type', 'group_id#=#group_id', 'status#=#status', 'create_time#between#create_time', 'update_time#between#update_time'
        ];
    }

    /**
     * 处理列表数据
     * @param $data
     * @return void
     */
    protected function _index_list_filter(&$data)
    {
        foreach ($data['data'] as &$vo) {
            $vo['exptime_info'] = (new \mowzs\lib\helper\CronExpressionParserHelper)->parse($vo['exptime']);
        }
    }
}
