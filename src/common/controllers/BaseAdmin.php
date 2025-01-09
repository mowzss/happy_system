<?php
declare (strict_types=1);

namespace app\common\controllers;

class BaseAdmin extends Base
{
    protected function initialize(): void
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->assign('layui_id', get_lay_table_id());
    }
}
