<?php

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemSpiderLogs;
use think\App;

class SpiderLogs extends BaseAdmin
{
    use CrudTrait;


    public function __construct(SystemSpiderLogs $model, App $app)
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
                    'field' => 'name',
                    'title' => '蜘蛛名称',
                    'align' => 'content',
                    'width' => 120,
                ], [
                    'field' => 'url',
                    'title' => '抓取页面',
                ], [
                    'field' => 'ip',
                    'title' => 'IP地址',
                    'width' => 140,
                ], [
                    'field' => 'isp',
                    'title' => 'ip归属地',
                    'width' => 180,
                ], [
                    'field' => 'user_agent',
                    'title' => 'UA',

                ], [
                    'field' => 'create_time',
                    'title' => '记录时间',
                    'width' => 160,

                ]
            ],
            'top_button' => [
                ['event' => 'del']

            ],
            'right_button' => [
                ['event' => 'del']
            ]

        ];
        $spiders = $this->app->config->get('spiders.list', []);
        $spiders = array_unique(array_values($spiders));
        $spiders = array_combine($spiders, $spiders);
        $this->forms = ['fields' => [
            [
                'type' => 'select',
                'name' => 'name',
                'label' => '蜘蛛名称',
                'options' => $spiders,
                'required' => true
            ], [
                'type' => 'text',
                'name' => 'url',
                'label' => '链接地址',
            ], [
                'type' => 'text',
                'name' => 'ip',
                'label' => 'IP地址',
            ], [
                'type' => 'text',
                'name' => 'user_agent',
                'label' => 'user_agent',
            ]
        ]];
        $this->search = [
            'id#=#id', 'name#like#name', 'url#like#url', 'ip#=#ip', 'user_agent#like#user_agent', 'create_time#between#create_time'
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
            try {
                $vo['isp'] = (new \Ip2Region())->simple($vo['ip']);
            } catch (\Exception $e) {
                $vo['isp'] = '未知';
            }
        }
    }

}
