<?php

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemOperationLog;
use app\model\user\UserInfo;
use think\App;

class OperationLog extends BaseAdmin
{
    use CrudTrait;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemOperationLog();
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
            ], [
                'field' => 'username',
                'title' => '用户',
            ],
            [
                'field' => 'node',
                'title' => '操作节点',
                'width' => 150,
            ],
            [
                'field' => 'desc',
                'title' => '描述',
            ],
            [
                'field' => 'ip',
                'title' => 'IP地址',
                'width' => 150,
            ],
            [
                'field' => 'user_agent',
                'title' => 'User-Agent',
            ],
            [
                'field' => 'create_time',
                'title' => '操作时间',
                'width' => 200,
                'sort' => true,
            ],
            'top_button' => [
                'event' => 'del'
            ],
            'right_button' => [
                'event' => 'del'
            ]
        ];
        $this->forms['fields'] = [];
        // 定义搜索条件
        $this->search = [
            'id#=#id',
            'uid#=#uid',
            'node#like#node',
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
