<?php
declare (strict_types=1);

namespace app\home\index;

use app\common\controllers\BaseHome;
use app\common\traits\UploadTraits;
use app\logic\system\ConfigLogic;
use mowzs\lib\helper\UserHelper;

class Upload extends BaseHome
{
    use UploadTraits;

    protected function initialize(): void
    {
        $this->setView();
        $this->user = UserHelper::instance()->getUserInfo();
        if ($this->app->config->get('happy.installed', false)) {
            $this->web_config = ConfigLogic::instance()->getConfigValue();
        } else {
            $this->redirect(urls('install/index/index'));
        }
    }
}
