<?php

declare (strict_types=1);

namespace app\home\index;

use app\common\controllers\BaseHome;
use mowzs\lib\helper\EventHelper;

class Index extends BaseHome
{
    /**
     * @return void
     */
    protected function initialize(): void
    {
        if (empty($this->app->config->get('install.installed'))) {
            $this->redirect('/install/index/index');
        }
    }

    /**
     * @return string
     */
    public function index(): string
    {
        EventHelper::instance()->listen('HomeIndex');
        return $this->fetch();
    }
}
