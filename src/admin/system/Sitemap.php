<?php

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemModule;
use app\model\system\SystemSitemap;
use think\App;

class Sitemap extends BaseAdmin
{
    use CrudTrait;

    /**
     * @var string
     */
    protected string $title = 'Sitemap网站地图';
    /**
     * 默认排序
     * @var array
     */
    protected array $default_order = [
        'create_time' => 'desc'
    ];
    /**
     * @var array|string[]
     */
    protected array $class_name = [
        'content' => '内容',
        'column' => '栏目',
        'tag' => '标签'
    ];
    /**
     * 模块列表
     * @var array
     */
    protected array $modules;

    public function __construct(SystemSitemap $model, App $app)
    {
        parent::__construct($app);
        $this->model = $model;
        $this->modules = (new SystemModule())->column('title', 'dir');
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
                    'field' => 'module',
                    'title' => '模块',
                    'width' => 160,
                    'align' => 'content'
                ], [
                    'field' => 'class',
                    'title' => '数据源',
                    'width' => 120,
                ], [
                    'field' => 'url',
                    'title' => '链接地址',
                    'align' => 'left',
                ], [
                    'field' => 'type',
                    'title' => '格式',
                    'width' => 80,

                ], [
                    'field' => 'create_time',
                    'title' => '生成时间',
                    'width' => 120,

                ]
            ],
            'top_button' => [
                'event' => 'del'

            ],
            'right_button' => [

            ]

        ];

        $this->forms = [
            'fields' => [
                [
                    'type' => 'select',
                    'name' => 'module',
                    'label' => '模块',
                    'options' => $this->modules,
                    'required' => true
                ], [
                    'type' => 'select',
                    'name' => 'class',
                    'label' => '数据',
                    'options' => $this->class_name,
                    'required' => true,
                ]
            ]
        ];
        $this->search = [
            'id#=#id', 'module#like#name', 'class#=#class', 'create_time#between#create_time'
        ];
    }

    /**
     * 处理列表数据
     * @param $data
     * @return void
     */
    protected function _index_list_filter(&$data): void
    {

        foreach ($data['data'] as &$vo) {
            $vo['class'] = $this->class_name[$vo['class']];
            $vo['module'] = $this->modules[$vo['module']];
//            $vo['url'] =  $vo['url'];
        }
    }


}
