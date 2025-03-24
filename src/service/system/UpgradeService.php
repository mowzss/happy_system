<?php

namespace app\service\system;

use app\common\util\SqlExecutor;
use app\model\system\SystemUpgradeLog;
use app\service\BaseService;
use think\Exception;
use think\facade\Log;

class UpgradeService extends BaseService
{

    /**
     * @return void
     */
    protected function initialize(): void
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * @param string $module
     * @param string $filename
     * @return bool
     * @throws Exception
     */
    public function isUpgrade(string $module = '', string $filename = ''): bool
    {
        if (empty($module)) {
            throw new \think\Exception('module empty');
        }
        if (empty($filename)) {
            throw new \think\Exception('filename empty');
        }
        Log::write('module:' . $module . ' filename:' . $filename, 'task');
        $query = SystemUpgradeLog::where('module', $module)->where('filename', $filename)->findOrEmpty();
        return !$query->isEmpty();
    }

    /**
     * 获取升级文件列表
     * @return array 返回一个数组，以子目录名为键，包含文件名、相对路径和是否已升级的信息。
     */
    public function getUpgradeFiles(): array
    {
        $allFilesData = [];
        $directoryPath = $this->app->getAppPath() . 'common/upgrade';

        // 创建一个递归迭代器来遍历目录
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directoryPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);

        // 收集所有需要检查的文件信息
        $filesToCheck = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'sql'])) {
                // 获取当前文件的完整路径
                $fullPath = $file->getPathname();
                // 计算相对于根目录的路径
                $relativePath = substr($fullPath, strlen($directoryPath) + 1);
                // 获取子目录名（如果有的话）
                $subDirName = dirname($relativePath);
                if ($subDirName === '.') {
                    $subDirName = 'root'; // 如果没有子目录，则默认为'root'
                } else {
                    $subDirName = basename($subDirName); // 使用最后一个子目录名作为键
                }

                // 收集需要检查的文件信息
                $filesToCheck[] = [
                    'module' => $subDirName,
                    'filename' => $file->getBasename(),
                    'relative_path' => $relativePath,
                ];
            }
        }
        // 组织返回的数据结构
        foreach ($filesToCheck as $fileInfo) {
            $subDirName = $fileInfo['module'];

            if (!isset($allFilesData[$subDirName])) {
                $allFilesData[$subDirName] = [];
            }
            $allFilesData[$subDirName][] = [
                'filename' => $fileInfo['filename'],
                'relative_path' => $fileInfo['relative_path'],
            ];
        }

        return $allFilesData;
    }


    /**
     * @param $module
     * @return true
     * @throws \Exception
     */
    public function install(): bool
    {
        $files = $this->getUpgradeFiles();
        // 执行install_files中的SQL文件及类的run方法
        foreach ($files as $module => $moduleFiles) {
            foreach ($moduleFiles as $fileOrClass) {
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
                SystemUpgradeLog::create(['module' => $module,
                    'filename' => $fileOrClass,
                    'create_time' => time(),
                ]);
            }
        }

        return true;
    }

}
