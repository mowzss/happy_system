<?php

namespace app\common\fun\system;

class SystemModule
{
    /**
     * @return array
     */
    public function getAllModule()
    {
        return \app\model\system\SystemModule::where('status', 1)->column('title', 'dir');
    }
}
