<?php

namespace app\common\fun\system;

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class SystemIcon
{
    public function getShowIcon(): array
    {
        try {
            return \app\model\system\SystemIcon::where('is_show', 1)->select()->toArray();
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return [];
        }
    }
}
