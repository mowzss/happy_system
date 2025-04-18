<?php

declare (strict_types=1);

namespace app\home\index;

use app\common\controllers\BaseHome;

class Index extends BaseHome
{
    /**
     * @return string
     */
    public function index()
    {
        return $this->fetch();
    }

}
