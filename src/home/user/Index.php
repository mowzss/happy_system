<?php

namespace app\home\user;

use app\common\controllers\BaseUser;
use app\model\user\UserInfo;
use app\model\user\UserLoginLog;
use mowzs\lib\Forms;
use think\Exception;

class Index extends BaseUser
{
    /**
     * 用户中心首页
     * @return string
     */
    public function index(): string
    {
        return $this->fetch();
    }

    /**
     * 默认页
     * @return string
     */
    public function main(): string
    {
        $this->assign('login_log', UserLoginLog::where('uid', $this->user['id'])->limit(20)->order('id desc')->select());
        return $this->fetch();
    }

    /**
     * @param string $uid
     * @return string
     */
    public function home(string $uid = ''): string
    {
        return $this->fetch();
    }

    /**
     * 修改密码
     * @return string
     */
    public function pass(): string
    {

        return $this->fetch();
    }

    /**
     * 修改资料
     * @return string
     * @throws Exception
     */
    public function profile(): string
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 获取配置文件中的表单字段名
            $formFields = array_column($this->getProfileForms(), 'name');
            // 过滤$data，使其只保留$formFields中存在的键
            $updata = array_intersect_key($data, array_flip($formFields));
            if (empty($updata['birthday'])) {
                unset($updata['birthday']);
            }
            UserInfo::where('id', $this->user['id'])->update($updata);
            $this->app->session->set('user', (new \app\model\user\UserInfo())->findOrEmpty($this->user['id'])->toArray());
            $this->success('资料更新成功');
        }
        $this->assign(['forms_html' => Forms::instance()->setValue($this->user)->render($this->getProfileForms(), '', 'code')]);
        return $this->fetch();
    }


    /**
     * @return array
     */
    protected function getProfileForms(): array
    {
        return [
            [
                'type' => 'text',
                'name' => 'nickname',
                'label' => '昵称',
            ], [
                'type' => 'image',
                'name' => 'avatar',
                'label' => '头像',
            ], [
                'type' => 'radio',
                'name' => 'sex',
                'label' => '性别',
                'options' => [
                    1 => '男',
                    2 => '女',
                ]
            ], [
                'type' => 'date',
                'name' => 'birthday',
                'label' => '生日',
            ], [
                'type' => 'textarea',
                'name' => 'describe',
                'label' => '签名',
            ],
        ];
    }
}
