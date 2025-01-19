<?php

declare (strict_types=1);

namespace app\home\index;

use app\common\controllers\BaseHome;
use app\service\system\UpgradeService;

class Index extends BaseHome
{
    /**
     * @return string
     */
    public function index()
    {
        return $this->fetch();
    }

    public function text()
    {
        dump(UpgradeService::instance()->getUpgradeFiles());
        echo '';
    }
}
