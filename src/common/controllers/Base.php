<?php
declare (strict_types=1);

namespace app\common\controllers;

use app\common\traits\system\ViewTheme;
use mowzs\lib\Controller;
use mowzs\lib\helper\UserHelper;

class Base extends Controller
{
    use ViewTheme;

    protected array|false $user;

    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->setView();
        $this->user = UserHelper::instance()->getUserInfo();
    }

}