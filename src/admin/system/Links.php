<?php
declare(strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemLinks;
use think\App;

class Links extends BaseAdmin
{
    use CrudTrait;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemLinks();
        $this->app->config->load('extra/links', 'links');
        $this->setParams();
    }

    protected function setParams(): void
    {
        $this->tables['tips'] = '分类及类型 需在项目配置文件/config/extra/links.php中进行设置';
        $this->tables['fields'] = [
            [
                'field' => 'id',
                'title' => 'ID',
                'width' => 80,
                'sort' => true,
            ],
            [
                'field' => 'column_name',
                'title' => '链接位置',
            ], [
                'field' => 'type_name',
                'title' => '链接类型',
            ], [
                'field' => 'title',
                'title' => '网站名称',
                'width' => 180,
            ], [
                'field' => 'url',
                'title' => '网站地址',
                'width' => 180,
            ],
            [
                'field' => 'start_time',
                'title' => '开始日期',
            ], [
                'field' => 'end_time',
                'title' => '结束日期',
            ],
            [
                'field' => 'list',
                'title' => '排序',
                'edit' => 'text'
            ], [
                'field' => 'status',
                'title' => '状态',
                'templet' => 'switch'
            ], [
                'field' => 'create_time',
                'title' => '创建时间',
            ],
        ];
        $this->forms['fields'] = [
            [
                'type' => 'text',
                'name' => 'title',
                'label' => '网站名称',
            ], [
                'type' => 'text',
                'name' => 'url',
                'label' => '网站地址',
            ], [
                'type' => 'datetime',
                'name' => 'start_time',
                'label' => '合作开始日期',
            ], [
                'type' => 'datetime',
                'name' => 'end_time',
                'label' => '合作结束日期',
            ],
            [
                'type' => 'select',
                'name' => 'cid',
                'label' => '友链位置',
                'options' => $this->app->config->get('links.column'),
            ], [
                'type' => 'select',
                'name' => 'type',
                'label' => '友链类型',
                'options' => $this->app->config->get('links.type'),
            ], [
                'type' => 'text',
                'name' => 'qq',
                'label' => '联系方式',
            ],
        ];
        $this->search = [
            'id#=#id', 'title#like#title', 'url#like#url', 'type#=#type', 'cid#=#cid', 'status#=#status', 'start_time#between#start_time', 'end_time#between#end_time', 'create_time#between#create_time', 'update_time#between#update_time'
        ];
    }

    /**
     * 处理列表数据
     * @param array $data
     * @return void
     */
    protected function _index_list_filter(array &$data): void
    {
        // 获取配置中的链接位置和类型
        $linksConfig = $this->app->config->get('links', []);
        $columns = $linksConfig['column'] ?? [];
        $types = $linksConfig['type'] ?? [];

        // 确保 data['data'] 存在并且是一个数组
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as &$v) {
                // 根据 cid 设置 column_name
                if (isset($v['cid']) && isset($columns[$v['cid']])) {
                    $v['column_name'] = $columns[$v['cid']];
                } else {
                    $v['column_name'] = '未知位置'; // 默认值，当 cid 未找到时
                }

                // 根据 type 设置 type_name
                if (isset($v['type']) && isset($types[$v['type']])) {
                    $v['type_name'] = $types[$v['type']];
                } else {
                    $v['type_name'] = '未知类型'; // 默认值，当 type 未找到时
                }
            }
            unset($v); // 解除引用
        }
    }

    /**
     * 保存前数据处理
     * @param $data
     * @return void
     */
    protected function _save_filter(&$data): void
    {
        if (empty($data['start_time'])) {
            $data['start_time'] = date('Y-m-d H:i:s', time());
        }
        if (empty($data['end_time'])) {
            $data['end_time'] = date('Y-m-d H:i:s');
        }
    }

    protected function _save_result()
    {
        $this->app->cache->tag('system_link')->clear();
    }
}
