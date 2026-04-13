<?php

namespace app\logic\system;

use mowzs\lib\BaseLogic;
use app\common\util\SqlExecutor;
use app\model\system\SystemModule;
use think\db\exception\DbException;
use mowzs\lib\Exception\LogicException;
use mowzs\lib\helper\ModuleInstallHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

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
            if (!in_array($fileInfo['content']['keyword'], $existingDirs, true)) {
                // 如果不存在，则添加到新模块列表中
                $newModules[] = $fileInfo;
            }
        }

        return $newModules;
    }

    /**
     * @param $module
     * @return true
     * @throws LogicException
     */
    public function install($module): bool
    {
        $info = $this->getModuleFileInfo($module);
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
                    throw new LogicException('执行SQL文件失败: ' . $e->getMessage());
                }
            } elseif (is_string($fileOrClass) && class_exists($fileOrClass)) {
                // 假设是类名
                try {
                    // 实例化类并调用run方法
                    $instance = app($fileOrClass);
                    if (method_exists($instance, 'run')) {
                        $instance->run();
                    } else {
                        throw new LogicException("类 {$fileOrClass} 没有 run 方法");
                    }
                } catch (\Exception $e) {
                    throw new LogicException('运行安装类失败: ' . $e->getMessage());
                }
            }
        }
        SystemModule::create([
            'title' => $info['name'],
            'dir' => $info['keyword'],
            'type' => 1,
            'is_copy' => $info['is_copy'] ? 1 : 0,
            'status' => 1, // 默认状态为启用
            'create_time' => time(),
            'update_time' => time(),
        ]);
        return true;
    }

    /**
     * 读取文件信息
     * @param $module
     * @return mixed|null
     */
    protected function getModuleFileInfo($module): mixed
    {
        $path = $this->app->getAppPath() . 'common' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . $module;
        $file = $path . DIRECTORY_SEPARATOR . 'info.php';
        if (file_exists($file)) {
            return include $file;
        }
        return null;
    }

    /**
     * 获取开启搜索功能的模块
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getSearchModule(): array
    {
        $data = SystemModule::where('status', 1)->field('dir,title')->select();
        $module = [];
        foreach ($data as $value) {

            if (!empty(sys_config($value['dir'] . '.is_open_search'))) {
                $module[$value['dir']] = $value['title'];
            }

        }
        return $module;
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getSitemapModule(): array
    {
        $data = SystemModule::where('status', 1)->field('dir,title')->select();
        $module = [];
        foreach ($data as $value) {
            try {
                if (!empty(sys_config($value['dir'] . '.is_open_sitemap'))) {
                    $module[$value['dir']] = $value['title'];
                }
            } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
                continue;
            }
        }
        return $module;
    }

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
