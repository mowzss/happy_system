<?php

declare (strict_types=1);

namespace app\home\index;

use app\common\controllers\BaseHome;
use mowzs\lib\helper\EventHelper;

class Index extends BaseHome
{
    /**
     * @return string
     */
    public function index(): string
    {
        EventHelper::instance()->listen('HomeIndex');
        return $this->fetch();
    }
}
