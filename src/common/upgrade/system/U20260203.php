<?php

namespace app\common\upgrade\system;

class U20260203
{
    /**
     * @return void
     */
    public function run(): void
    {
        (new \app\model\system\SystemEvent)->insertAll([
            ['name' => 'UserRegister', 'info' => '用户注册后', 'params_info' => '用户模型'],
            ['name' => 'UserLogin', 'info' => '用户登录后', 'params_info' => '用户模型'],
        ]);
    }
}
