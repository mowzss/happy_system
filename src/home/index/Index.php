<?php

declare (strict_types=1);

namespace app\home\index;

use app\common\controllers\BaseHome;

class Index extends BaseHome
{
    /**
     * 系统首页
     * @return string
     */
    public function index(): string
    {

        return $this->fetch();
    }

}
