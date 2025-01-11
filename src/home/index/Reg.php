<?php
declare(strict_types=1);

namespace app\home\index;

use app\common\controllers\BaseHome;

class Reg extends BaseHome
{
    /**
     * 注册
     * @return string
     */
    public function index(): string
    {
        if ($this->request->isPost()) {
            $this->success('注册成功');
        }
        return $this->fetch();
    }
}
