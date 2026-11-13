<?php

namespace app\admin\system;

use think\App;
use app\common\traits\CrudTrait;
use app\common\controllers\BaseAdmin;
use app\model\system\SystemSpiderDate;

/**
 * 蜘蛛每日抓取统计（只读）
 */
class SpiderDate extends BaseAdmin
{
    use CrudTrait;

    protected array $default_order = ['date' => 'desc'];

    public function __construct(SystemSpiderDate $model, App $app)
    {
        parent::__construct($app);
        $this->model = $model;
        $this->setParams();
    }

    protected function setParams(): void
    {
        $this->tables = [
            'title' => '蜘蛛每日统计',
            'fields' => [
                [
                    'field' => 'id',
                    'title' => 'ID',
                    'sort'  => true,
                ],
                [
                    'field' => 'date',
                    'title' => '统计日期',
                    'sort'  => true,
                ],
                [
                    'field' => 'name',
                    'title' => '蜘蛛名称',
                ],
                [
                    'field' => 'total_visits',
                    'title' => '访问次数',
                    'sort'  => true,
                ],
                [
                    'field' => 'unique_urls',
                    'title' => '不同URL数',
                    'sort'  => true,
                ],
                [
                    'field'  => 'avg_per_url',
                    'title'  => '平均每URL访问',
                    'templet'=> 'function(d){ return d.unique_urls > 0 ? (d.total_visits / d.unique_urls).toFixed(2) : "-"; }',
                ],
            ],
            'top_button'  => false,
            'right_button'=> false,
        ];

        // forms 必须有 name 字段，否则 getSearchFields() 用 array_column 找不到映射
        $this->forms = ['fields' => [
            [
                'type'  => 'text',
                'name'  => 'date',
                'label' => '日期（起始）',
            ],
            [
                'type'  => 'text',
                'name'  => 'date_end',
                'label' => '日期（结束）',
            ],
            [
                'type'  => 'select',
                'name'  => 'name',
                'label' => '蜘蛛',
                'options' => [],
            ],
        ]];

        // search 语法：表单name#操作符#请求参数
        // between 操作符会自动把 date 与 date_end 拼成 >= 起始 AND <= 结束
        $this->search = [
            'date#between#date',
            'name#like#name',
        ];

        // 动态注入蜘蛛名称下拉选项
        $names = $this->model->distinct(true)->order('name', 'asc')->column('name');
        if ($names) {
            $nameOptions = array_combine($names, $names);
            foreach ($this->forms['fields'] as &$field) {
                if (($field['name'] ?? '') === 'name') {
                    $field['options'] = $nameOptions;
                }
            }
            unset($field);
        }
    }

    public function add()
    {
        $this->error('只读');
    }

    public function edit($id = '')
    {
        $this->error('只读');
    }

    public function del($id = '')
    {
        $this->error('只读');
    }

    public function quickEdit($id): void
    {
        $this->error('只读');
    }
}
