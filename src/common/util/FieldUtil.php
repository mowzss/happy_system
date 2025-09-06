<?php
declare (strict_types=1);

namespace app\common\util;

use mowzs\lib\module\logic\FieldBaseLogic;

class FieldUtil
{
    public function field_list()
    {
        FieldBaseLogic::instance();
    }
}
