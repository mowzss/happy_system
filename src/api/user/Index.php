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
        // GET：获取用户资料
        if ($this->request->isGet()) {
            $user = (new \app\model\user\UserInfo())
                ->findOrEmpty($this->uid)
                ->hidden(['password'])
                ->toArray();
            if (empty($user)) {
                $this->json(['code' => 1, 'msg' => '用户不存在']);
            }
            // 默认签名
            if (empty($user['describe'])) {
                $user['describe'] = '暂无签名';
            }
            $this->json($user);
        }
        // POST：更新用户资料
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 只保留允许更新的字段
            $updateData = array_intersect_key($data, array_flip($this->isUpdateField));

            // 如果没有可更新字段，直接返回错误
            if (empty($updateData)) {
                $this->json([], 400, '无可更新字段');
            }

            // 实例化模型并尝试更新
            $userModel = new \app\model\user\UserInfo();
            $user = $userModel->findOrEmpty($this->uid);

            if ($user->isEmpty()) {
                $this->json([], 400, '用户不存在');
            }
            try {// 执行更新（只更新指定字段）
                $user->save($updateData);
            } catch (\Exception $e) {
                json([], 500, $e->getMessage());
            }
            $this->json(['msg' => '更新成功']);
        }
    }

    /**
     * 可更新字段
     * @var array|string[]
     */
    protected array $isUpdateField = ['nickname', 'avatar', 'describe', 'birthday', 'sex'];
}
