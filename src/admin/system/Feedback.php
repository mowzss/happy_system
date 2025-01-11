<?php
declare(strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemFeedback;
use think\App;

class Feedback extends BaseAdmin
{
    use CrudTrait;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemFeedback();
        $this->app->config->load('extra/feedback', 'feedback'); // 加载反馈配置
        $this->setParams();
    }

    protected function setParams(): void
    {
        $this->tables['tips'] = '反馈类型 需在项目配置文件/config/extra/feedback.php中进行设置';
        // 定义表格字段
        $this->tables['fields'] = [
            [
                'field' => 'id',
                'title' => 'ID',
                'width' => 80,
                'sort' => true,
            ],
            [
                'field' => 'category_name',
                'title' => '类别',
            ],
            [
                'field' => 'title',
                'title' => '标题',
                'width' => 180,
            ],
            [
                'field' => 'page_url',
                'title' => '举报页面',
                'width' => 250,
            ],
            [
                'field' => 'content',
                'title' => '内容',
                'type' => 'textarea',
            ],
            [
                'field' => 'status',
                'title' => '状态',
                'templet' => 'switch'
            ],
            [
                'field' => 'create_time',
                'title' => '创建时间',
            ],
            [
                'field' => 'handled_by',
                'title' => '处理人',
            ],
            [
                'field' => 'handled_time',
                'title' => '处理时间',
            ],
            [
                'field' => 'response',
                'title' => '回复内容',
                'type' => 'textarea',
            ],
        ];

        // 定义表单字段
        $this->forms['fields'] = [
            [
                'type' => 'text',
                'name' => 'title',
                'label' => '标题',
            ],
            [
                'type' => 'text',
                'name' => 'contact_info',
                'label' => '联系方式',
                'help' => '联系方式 (如邮箱、电话等)'
            ],
            [
                'type' => 'select',
                'name' => 'category',
                'label' => '类别',
                'options' => $this->app->config->get('feedback.category'),
            ],
            [
                'type' => 'text',
                'name' => 'page_url',
                'label' => '举报页面',
            ],
            [
                'type' => 'textarea',
                'name' => 'content',
                'label' => '内容',
            ],
            [
                'type' => 'textarea',
                'name' => 'response',
                'label' => '回复内容',
            ],
            [
                'type' => 'datetime',
                'name' => 'handled_time',
                'label' => '处理时间',
            ],
        ];

        // 定义搜索条件
        $this->search = [
            'id#=#id',
            'title#like#title',
            'category#=#category',
            'status#=#status',
            'create_time#between#create_time',
            'handled_time#between#handled_time',
            'uid#=#user_id',
            'contact_info#like#contact_info',
        ];
    }

    /**
     * 处理列表数据
     * @param array $data
     * @return void
     */
    protected function _index_list_filter(array &$data): void
    {
        // 获取配置中的反馈类别
        $feedbackConfig = $this->app->config->get('feedback', []);
        $categories = $feedbackConfig['category'] ?? [];

        // 确保 data['data'] 存在并且是一个数组
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as &$v) {
                // 根据 category 设置 category_name
                if (isset($v['category']) && isset($categories[$v['category']])) {
                    $v['category_name'] = $categories[$v['category']];
                } else {
                    $v['category_name'] = '未知类别'; // 默认值，当 category 未找到时
                }

                // 如果有处理人ID，设置处理人的信息（假设有一个获取用户信息的方法）
            }
            unset($v); // 解除引用
        }
    }
}
