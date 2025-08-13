<?php
declare(strict_types=1);

namespace app\common\controllers;

use mowzs\lib\helper\AuthHelper;

class BaseUser extends Base
{
    protected function initialize(): void
    {
        parent::initialize();
        $this->app->event->trigger('UserControllerInit');
        if (!AuthHelper::instance()->isLogin()) {
            $this->error('请先登录账号', '', urls('index/login/index'));
        }

    }

    /**
     * 判断是否layui 数据表格请求
     * @return bool
     */
    protected function isLayTable(): bool
    {
        return $this->request->isAjax() && ($this->request->param('out') == 'json' || $this->request->header('out') == 'json');
    }
}
