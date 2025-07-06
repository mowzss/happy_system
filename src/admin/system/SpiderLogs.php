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
                    'width' => 180,
                ], [
                    'field' => 'url',
                    'title' => '抓取页面',
                    'width' => 180,
                ], [
                    'field' => 'ip',
                    'title' => 'IP地址',
                ], [
                    'field' => 'isp',
                    'title' => 'ip归属地',
                ], [
                    'field' => 'user_agent',
                    'title' => 'UA',
                    'width' => 180,

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

        $this->forms = [];
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
