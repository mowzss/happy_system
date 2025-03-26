<?php
declare(strict_types=1);

namespace app\logic\system;

use app\common\util\SqlExecutor;
use app\logic\BaseLogic;
use app\model\system\SystemModule;
use mowzs\lib\helper\ModuleInstallHelper;

class ModuleLogic extends BaseLogic
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

    /**
     * @param $module
     * @return true
     * @throws \Exception
     */
    public function install($module): bool
    {
        $info = $this->getModuleInfo($module);
        // 执行install_files中的SQL文件及类的run方法
        foreach ($info['install_files'] as $fileOrClass) {
            if (is_string($fileOrClass) && !class_exists($fileOrClass)) {
                // 假设是SQL文件
                $sqlFilePath = DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $fileOrClass;

                try {
                    // 创建SqlExecutor实例并执行SQL文件
                    $sqlExecutor = new SqlExecutor();
                    $sqlExecutor->execute($sqlFilePath);
                } catch (\Exception $e) {
                    throw new \Exception('执行SQL文件失败: ' . $e->getMessage());
                }
            } elseif (is_string($fileOrClass) && class_exists($fileOrClass)) {
                // 假设是类名
                try {
                    // 实例化类并调用run方法
                    $instance = app($fileOrClass);
                    if (method_exists($instance, 'run')) {
                        $instance->run();
                    } else {
                        throw new \Exception("类 {$fileOrClass} 没有 run 方法");
                    }
                } catch (\Exception $e) {
                    throw new \Exception('运行安装类失败: ' . $e->getMessage());
                }
            }
        }
        SystemModule::create(['title' => $info['name'],
            'dir' => $info['keyword'],
            'type' => 1,
            'is_copy' => $info['is_copy'] ? 1 : 0,
            'status' => 1, // 默认状态为启用
            'create_time' => time(),
            'update_time' => time(),]);
        return true;
    }

    /**
     * 读取文件信息
     * @param $module
     * @return mixed|null
     */
    protected function getModuleInfo($module): mixed
    {
        $file = $this->app->getAppPath() . 'common' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'info.php';
        if (file_exists($file)) {
            return include $file;
        }
        return null;
    }
}
