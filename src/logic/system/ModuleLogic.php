<?php

namespace app\logic\system;

use app\model\system\SystemModule;
use mowzs\lib\BaseLogic;

class ModuleLogic extends BaseLogic
{
    /**
     * 获取所有模块
     * @return array
     */
    public function getModuleAll(): array
    {
        return SystemModule::where('status', 1)->column('title', 'dir');
    }

    /**
     * 通过模块目录获取模块名称
     * @param string $module
     * @return string
     */
    public function getModuleNameByDir(string $module): string
    {
        return SystemModule::where('dir', $module)->value('title') ?? $module;
    }
}
