<?php

namespace app\api\user;

use app\common\controllers\BaseApi;

class Index extends BaseApi
{
    /**
     * 获取用户信息
     * @return void
     */
    public function profile(): void
    {
        if ($this->request->isGet()) {
            $user = (new \app\model\user\UserInfo)->findOrEmpty($this->uid)->hidden(['password'])->toArray();
            if (empty($user)) {
                $this->json(['code' => 1, 'msg' => '用户不存在']);
            }
            $user['describe'] = '暂无签名';
            $this->json($user);
        }
        if ($this->request->isPost()) {
            $data = $this->request->post();
        }

    }
}
