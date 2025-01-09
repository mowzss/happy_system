<?php
declare (strict_types=1);

namespace app\admin\user;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\user\UserGroup;
use app\model\user\UserInfo;
use mowzs\lib\Forms;
use think\App;
use think\Exception;

/**
 * 会员信息
 */
class Info extends BaseAdmin
{
    use CrudTrait;

    public function __construct(App $app, UserInfo $userInfo)
    {
        parent::__construct($app);
        $this->model = $userInfo;
        $this->setParams();
    }

    protected function setParams(): void
    {
        $this->tables = [
            'fields' => [
                [
                    'field' => 'id',
                    'title' => 'ID',
                    'width' => 80,
                    'sort' => true,
                ], [
                    'field' => 'username',
                    'title' => '用户名',
                    'width' => 150,
                ],
                [
                    'field' => 'nickname',
                    'title' => '昵称',
                    'width' => 150,
                ],
                [
                    'field' => 'group_name',
                    'title' => '用户组',
                    'width' => 150,
                ],
                [
                    'field' => 'login_num',
                    'title' => '登录次数',
                    'width' => 80,
                    'sort' => true,
                ],
                [
                    'field' => 'last_time',
                    'title' => '最近登录时间',
                    'sort' => true,
                ],
                [
                    'field' => 'last_ip',
                    'title' => '最近登录IP',
                ],
                [
                    'field' => 'status',
                    'title' => '状态',
                    'templet' => 'switch'
                ],
                [
                    'field' => 'create_time',
                    'title' => '创建时间',
                    'sort' => true,
                ],
            ],
            'top_button' => [

            ],
            'right_button' => [
                [
                    'event' => '',
                    'type' => 'data-modal',
                    'url' => urls('password', ['id' => '__id__']),
                    'name' => '设置密码',
                    'class' => '',//默认包含 layui-btn layui-btn-xs
                ],
                ['event' => 'edit'],
                ['event' => 'del'],
            ]

        ];
        $this->forms = [
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'username',
                    'label' => '用户名',
                ],
                [
                    'type' => 'text',
                    'name' => 'mobile',
                    'label' => '手机号',
                ],
                [
                    'type' => 'text',
                    'name' => 'nickname',
                    'label' => '昵称',
                ],
                [
                    'type' => 'select',
                    'name' => 'group_id',
                    'label' => '用户组',
                    'options' => $this->getUserGroupOptions(),
                ],
            ]
        ];  // 定义搜索条件
        $this->search = [
            'id#=#id',
            'username#like#username',
            'nickname#like#nickname',
            'mobile#like#mobile',
            'group_id#=#group_id',
            'login_num#=#login_num',
            'last_time#between#last_time',
            'last_ip#like#last_ip',
            'create_time#between#create_time',
            'update_time#between#update_time',
        ];
    }

    /**
     * 处理列表数据
     * @param array $data
     * @return void
     */
    protected function _index_list_filter(array &$data): void
    {
        // 获取所有用户组的名称映射
        $groupNames = $this->getUserGroupOptions();

        // 确保 data['data'] 存在并且是一个数组
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as &$v) {
                // 设置用户组名称
                if (!empty($v['group_id']) && isset($groupNames[$v['group_id']])) {
                    $v['group_name'] = $groupNames[$v['group_id']];
                } else {
                    $v['group_name'] = '未知用户组';
                }
            }
            unset($v); // 解除引用
        }
    }

    /**
     * 获取用户组选项
     * @return array
     */
    protected function getUserGroupOptions(): array
    {
        // 假设有一个 UserGroup 模型用于获取用户组信息
        return UserGroup::where('status', 1)->column('name', 'id');
    }

    /**
     * 重置密码
     * @auth true
     * @return string
     * @throws Exception
     */
    public function password(): string
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('id不能为空');
        }
        $user_info = $this->model->findOrEmpty($id);
        if ($user_info->isEmpty()) {
            $this->error('用户不存在');
        }
        if ($this->request->isPost()) {
            // 获取新密码及其确认
            $password = $this->request->post('password');
            $password2 = $this->request->post('password2');

            // 检查密码是否一致
            if ($password !== $password2) {
                $this->error('两次输入的密码不一致！');
            }

            // 检查密码长度
            if (strlen($password) < 8) {
                $this->error('密码长度不能小于8位');
            }

            // 检查密码是否包含字母和数字
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
                $this->error('密码需要同时包含字母和数字');
            }

            // 更新用户密码（假设有一个方法来安全地哈希密码）
            $hashedPassword = password_hash(md5($password), PASSWORD_DEFAULT);
            $user_info->password = $hashedPassword;

            // 保存更新到数据库
            if ($user_info->save()) {
                $this->success('密码重置成功');
            } else {
                $this->error('密码重置失败，请稍后再试');
            }
        }
        return Forms::instance()->render([
            [
                'type' => 'text',
                'name' => 'password',
                'label' => '密码',
                'required' => true
            ], [
                'type' => 'text',
                'name' => 'password2',
                'label' => '确认密码',
                'required' => true
            ],
        ]);
    }
}
