<?php
declare(strict_types=1);

namespace app\admin\user;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\user\UserInfo;
use app\model\user\UserPointsLog;
use think\App;

class PointsLog extends BaseAdmin
{
    use CrudTrait;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new UserPointsLog();
        $this->setParams();
    }

    protected function setParams(): void
    {
        // 定义表格字段
        $this->tables['fields'] = [
            [
                'field' => 'id',
                'title' => 'ID',
                'width' => 80,
                'sort' => true,
            ],
            [
                'field' => 'username',
                'title' => '用户名',
                'width' => 150,
            ],
            [
                'field' => 'points',
                'title' => '积分变动',
                'width' => 100,
            ],
            [
                'field' => 'type',
                'title' => '变动类型',
                'width' => 150,
            ],
            [
                'field' => 'description',
                'title' => '变动描述',
                'type' => 'textarea',
            ],
            [
                'field' => 'create_time',
                'title' => '创建时间',
                'width' => 200,
                'sort' => true,
            ],
        ];

        // 定义表单字段
        $this->forms['fields'] = [
            [
                'type' => 'text',
                'name' => 'uid',
                'label' => '用户ID',
                'readonly' => true,
            ],
            [
                'type' => 'number',
                'name' => 'points',
                'label' => '积分变动',
                'readonly' => true,
            ],
            [
                'type' => 'text',
                'name' => 'type',
                'label' => '变动类型',
                'readonly' => true,
            ],
            [
                'type' => 'textarea',
                'name' => 'desc',
                'label' => '变动描述',
                'readonly' => true,
            ],
            [
                'type' => 'datetime',
                'name' => 'create_time',
                'label' => '创建时间',
                'readonly' => true,
            ],
        ];

        // 定义搜索条件
        $this->search = [
            'id#=#id',
            'username#like#username',
            'points#=#points',
            'type#like#type',
            'create_time#between#create_time',
        ];
    }

    /**
     * 处理列表数据
     * @param array $data
     * @return void
     */
    protected function _index_list_filter(array &$data): void
    {
        // 确保 data['data'] 存在并且是一个数组
        if (isset($data['data']) && is_array($data['data'])) {
            // 提取当前分页数据中的所有 uid
            $userIds = array_unique(array_column($data['data'], 'uid'));

            // 如果有 uid，查询对应的用户名
            if (!empty($userIds)) {
                // 使用 whereIn 查询相关的用户信息
                $userNames = UserInfo::whereIn('id', $userIds)
                    ->column('username', 'id');

                // 将用户名绑定到日志数据中
                foreach ($data['data'] as &$v) {
                    $v['username'] = $userNames[$v['uid']] . '(id：' . $v['uid'] . ')' ?? '未知用户';
                }
                unset($v); // 解除引用
            } else {
                // 如果没有 uid，直接设置默认值
                foreach ($data['data'] as &$v) {
                    $v['username'] = '未知用户';
                }
                unset($v); // 解除引用
            }
        }
    }
}
