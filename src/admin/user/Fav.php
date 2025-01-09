<?php

namespace app\admin\user;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\user\User;
use app\model\user\UserFav;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class Fav extends BaseAdmin
{
    use CrudTrait;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new UserFav();
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
                'field' => 'user_name',
                'title' => '用户名',
            ],
            [
                'field' => 'module',
                'title' => '模块',
            ],
            [
                'field' => 'content_title',
                'title' => '内容标题',
                'width' => 250,
            ],
            [
                'field' => 'content_url',
                'title' => '内容链接',
                'width' => 300,
            ],
            [
                'field' => 'create_time',
                'title' => '收藏时间',
            ],
            [
                'field' => 'update_time',
                'title' => '更新时间',
            ],
            [
                'field' => 'status',
                'title' => '状态',
                'templet' => 'switch'
            ],
        ];

        // 定义表单字段
        $this->forms['fields'] = [
            [
                'type' => 'text',
                'name' => 'module',
                'label' => '模块',
            ],
            [
                'type' => 'text',
                'name' => 'mid',
                'label' => '模型ID',
            ],
            [
                'type' => 'text',
                'name' => 'content_id',
                'label' => '内容ID',
            ],
            [
                'type' => 'text',
                'name' => 'content_title',
                'label' => '内容标题',
            ], [
                'type' => 'text',
                'name' => 'uid',
                'label' => '用户id',
            ],
            [
                'type' => 'text',
                'name' => 'content_url',
                'label' => '内容链接',
            ],
            [
                'type' => 'datetime',
                'name' => 'create_time',
                'label' => '收藏时间',
            ],
            [
                'type' => 'datetime',
                'name' => 'update_time',
                'label' => '更新时间',
            ],
            [
                'type' => 'radio',
                'name' => 'status',
                'label' => '状态',
                'options' => [1 => '正常', 0 => '已删除'],
            ],
        ];

        // 定义搜索条件
        $this->search = [
            'id#=#id',
            'uid#=#user_id',
            'module#like#module',
            'content_title#like#content_title',
            'content_url#like#content_url',
            'create_time#between#create_time',
            'update_time#between#update_time',
            'status#=#status',
        ];
    }

    /**
     * 处理列表数据
     * @param array $data
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _index_list_filter(array &$data): void
    {
        // 确保 data['data'] 存在并且是一个数组
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as &$v) {
                // 设置用户名
                if (!empty($v['uid'])) {
                    $user = $this->getUserById($v['uid']);
                    $v['user_name'] = $user ? $user->username : '未知用户';
                } else {
                    $v['user_name'] = '';
                }
            }
            unset($v); // 解除引用
        }
    }

    /**
     * 根据用户ID获取用户信息
     * @param int $userId
     * @return mixed|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function getUserById(int $userId)
    {
        // 假设有一个 User 模型用于获取用户信息
        return User::find($userId);
    }
}
