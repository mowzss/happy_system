<?php

namespace app\admin\system;

use think\App;
use app\common\traits\CrudTrait;
use app\common\controllers\BaseAdmin;
use app\model\system\SystemSpiderHourly;

/**
 * 蜘蛛每小时抓取统计（只读）
 */
class SpiderHourly extends BaseAdmin
{
    use CrudTrait;

    protected array $default_order = ['date' => 'desc', 'hour' => 'desc'];

    public function __construct(SystemSpiderHourly $model, App $app)
    {
        parent::__construct($app);
        $this->model = $model;
        $this->setParams();
    }

    protected function setParams(): void
    {
        $this->tables = [
            'title' => '蜘蛛每小时统计',
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
                    'field' => 'hour',
                    'title' => '小时',
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
                'name'  => 'hour',
                'label' => '小时',
                'options' => [
                    '0'  => '0点',  '1'  => '1点',  '2'  => '2点',  '3'  => '3点',
                    '4'  => '4点',  '5'  => '5点',  '6'  => '6点',  '7'  => '7点',
                    '8'  => '8点',  '9'  => '9点',  '10' => '10点', '11' => '11点',
                    '12' => '12点', '13' => '13点', '14' => '14点', '15' => '15点',
                    '16' => '16点', '17' => '17点', '18' => '18点', '19' => '19点',
                    '20' => '20点', '21' => '21点', '22' => '22点', '23' => '23点',
                ],
            ],
            [
                'type'  => 'select',
                'name'  => 'name',
                'label' => '蜘蛛',
                'options' => [],
            ],
        ]];

        $this->search = [
            'date#between#date',
            'hour#=#hour',
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
