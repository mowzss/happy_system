<?php
declare (strict_types=1);

namespace app\common\controllers;

use app\common\traits\system\ViewTheme;
use app\logic\system\ConfigLogic;
use mowzs\lib\Controller;
use mowzs\lib\helper\UserHelper;

class Base extends Controller
{
    use ViewTheme;

    /**
     * 用户信息
     * @var array|false
     */
    protected array|false $user;
    /**
     * 网站配置信息
     * @var mixed
     */
    protected mixed $web_config;

    protected function initialize(): void
    {
        $this->setView();
        $this->user = UserHelper::instance()->getUserInfo();
        $this->web_config = ConfigLogic::instance()->getConfigValue();
        parent::initialize(); // TODO: Change the autogenerated stub
    }

}
