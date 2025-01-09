<?php

namespace app\admin\index;

use app\common\controllers\BaseAdmin;
use app\model\user\UserInfo;
use mowzs\lib\helper\AuthHelper;

/**
 * 登录入口
 */
class Login extends BaseAdmin
{
    /**
     * 登录页面
     * @return string
     */
    public function index(): string
    {
        if (AuthHelper::instance()->isLogin()) {
            $this->redirect(urls('index/index/index'));
        }
        if (request()->isPost()) {
            $data = $this->request->post();
            try {
                $this->validate($data, [
                    'username' => 'require',
                    'password' => 'require',
                ], [
                    'username.require' => '用户名不能为空',
                    'password.require' => '密码不能为空',
                ]);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            $user = UserInfo::where('username', $data['username'])->findOrEmpty();
            if ($user->isEmpty()) {
                $this->error('账号或密码错误');
            }
            if (!password_verify($data['password'], $user->password)) {
                $this->error('账号或密码错误!');
            }
            $this->app->session->set('user', $user->toArray());
            $save_data = [
                'id' => $user['id'],
                'last_time' => time(),
                'last_ip' => $this->request->ip()
            ];
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                // 如果是这样，则创建新散列，替换旧散列
                $save_data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            $user->inc('login_num')->save($save_data);
            $this->success('登陆成功', [], urls('index/index'));
        }
        return $this->fetch();
    }

}
