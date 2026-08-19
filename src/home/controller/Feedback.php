<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controllers\BaseHome;

class Feedback extends BaseHome
{
    /**
     * 举报反馈
     * @return string
     */
    public function index(): string
    {
        return $this->fetch();
    }
}
