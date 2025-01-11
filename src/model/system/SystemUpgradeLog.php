<?php
declare(strict_types=1);

namespace app\model\system;

use think\Model;

/**
 * 系统sql升级日志
 */
class SystemUpgradeLog extends Model
{
    /**
     * @param string $module
     * @param string $filename
     * @return bool
     */
    public function chekLog(string $module = '', string $filename = ''): bool
    {
        $this->where('module', $module)->where('filename', $filename)->findOrEmpty();
        return !$this->isEmpty();
    }
}
