<?php

namespace app\service\system;

use app\model\system\SystemModule;
use app\service\BaseService;
use mowzs\lib\helper\ModuleInstallHelper;

class ModuleService extends BaseService
{
    /**
     * 未安装模块
     *
     * @return array 返回过滤后的新模块信息数组。
     */
    public function notInstalledModules(): array
    {
        // 获取所有已存在的模块目录
        $existingDirs = SystemModule::column('dir');

        // 使用ModuleInstallHelper实例扫描并读取info.php文件
        $filesData = ModuleInstallHelper::instance()->scanAndReadInfoPhpFiles();

        // 过滤掉已在数据库中的模块
        $newModules = [];
        foreach ($filesData as $fileInfo) {
            if (!in_array($fileInfo['content']['keyword'], $existingDirs)) {
                // 如果不存在，则添加到新模块列表中
                $newModules[] = $fileInfo;
            }
        }

        return $newModules;
    }
}
