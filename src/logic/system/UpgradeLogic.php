<?php

namespace app\logic\system;

use app\common\util\SqlExecutor;
use app\model\system\SystemUpgradeLog;
use mowzs\lib\BaseLogic;
use think\Exception;

class UpgradeLogic extends BaseLogic
{
    protected function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 判断指定模块的指定文件是否已执行过升级
     *
     * @param string $module
     * @param string $filename
     * @return bool
     * @throws Exception
     */
    public function isUpgrade(string $module = '', string $filename = ''): bool
    {
        if (empty($module)) {
            throw new Exception('module empty');
        }
        if (empty($filename)) {
            throw new Exception('filename empty');
        }
        $exists = SystemUpgradeLog::where('module', $module)
            ->where('filename', $filename)
            ->find();
        return !empty($exists);
    }

    /**
     * 获取所有升级文件，并按模块分组，每个模块内按 filename 升序排列
     *
     * @return array
     */
    public function getUpgradeFiles(): array
    {
        $allFilesData = [];
        $directoryPath = $this->app->getAppPath() . 'common/upgrade';

        if (!is_dir($directoryPath)) {
            return $allFilesData;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directoryPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $filesToCheck = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'sql'], true)) {
                $fullPath = $file->getPathname();
                $relativePath = substr($fullPath, strlen($directoryPath) + 1);
                $subDir = dirname($relativePath);

                $module = ($subDir === '.' || $subDir === '\\') ? 'root' : basename($subDir);

                $filesToCheck[] = [
                    'module' => $module,
                    'filename' => $file->getBasename(),
                    'relative_path' => $relativePath,
                ];
            }
        }

        // 按 module 分组
        foreach ($filesToCheck as $info) {
            $allFilesData[$info['module']][] = [
                'filename' => $info['filename'],
                'relative_path' => $info['relative_path'],
            ];
        }

        // 对每个模块内的文件按 filename 升序排序（关键！）
        foreach ($allFilesData as $module => &$files) {
            usort($files, fn($a, $b) => strcmp($a['filename'], $b['filename']));
        }
        unset($files); // 解除引用

        return $allFilesData;
    }

    /**
     * 安装时执行所有升级文件（通常用于首次安装）
     *
     * @return bool
     * @throws \Exception
     */
    public function install(): bool
    {
        $files = $this->getUpgradeFiles();

        foreach ($files as $module => $moduleFiles) {
            foreach ($moduleFiles as $file) {
                $filename = $file['filename'];
                $className = str_replace('.php', '', $filename);
                $class = "\\app\common\upgrade\\{$module}\\{$className}";

                if (!class_exists($class)) {
                    // SQL 文件
                    $sqlFilePath = DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $filename;
                    try {
                        $sqlExecutor = new SqlExecutor();
                        $sqlExecutor->execute($sqlFilePath);
                    } catch (\Exception $e) {
                        throw new \Exception('执行SQL文件失败: ' . $e->getMessage());
                    }
                } else {
                    // PHP 类
                    try {
                        $instance = app($class);
                        if (!method_exists($instance, 'run')) {
                            throw new \Exception("类 {$filename} 没有 run 方法");
                        }
                        $instance->run();
                    } catch (\Exception $e) {
                        throw new \Exception('运行安装类失败: ' . $e->getMessage());
                    }
                }

                SystemUpgradeLog::create([
                    'module' => $module,
                    'filename' => $filename,
                    'create_time' => time(),
                ]);
            }
        }

        return true;
    }
}
