<?php


namespace app\logic\system;

use happy\admin\libs\BaseLogic;
use app\common\util\SqlExecutor;
use app\model\system\SystemModule;
use app\model\system\SystemMenu;
use app\model\system\SystemConfig;
use app\model\system\SystemConfigGroup;
use think\facade\Db;
use think\db\exception\DbException;
use think\db\exception\DataNotFoundException;
use happy\admin\libs\Exception\LogicException;
use think\db\exception\ModelNotFoundException;
use happy\admin\libs\helper\ModuleInstallHelper;

class ModuleLogic extends BaseLogic
{
    /**
     * 系统核心模块，禁止卸载
     * @var string[]
     */
    protected const PROTECTED_MODULES = ['system'];

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
        $path = $this->app->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . $module;
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

    /**
     * 卸载模块
     * 仅清理 info.php 中 uninstall 声明范围内的数据表、菜单、配置与模块文件
     *
     * @param string $module 模块目录（即 info.php 中的 keyword）
     * @return array 卸载结果统计
     * @throws LogicException
     */
    public function uninstall(string $module): array
    {
        $module = trim($module);
        if ($module === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $module)) {
            throw new LogicException('模块目录不合法');
        }
        if (in_array($module, self::PROTECTED_MODULES, true)) {
            throw new LogicException('系统核心模块禁止卸载');
        }
        $record = SystemModule::where('dir', $module)->find();
        if (empty($record)) {
            throw new LogicException('模块未安装或记录不存在');
        }
        $info = $this->getModuleFileInfo($module);
        if (!is_array($info) || ($info['keyword'] ?? '') !== $module) {
            throw new LogicException('info.php 不存在或模块标记与目录不一致');
        }
        $manifest = $this->normalizeUninstallManifest($module, $info['uninstall'] ?? null);

        $prefix = $this->getDbPrefix();

        // 1. 删除数据表；DDL 会隐式提交，先执行，失败即中止（模块记录保留，便于重试）
        $dropped = [];
        foreach ($manifest['tables'] as $table) {
            $tableName = $this->resolveModuleTable($module, $table, $prefix);
            if (!$this->tableExists($tableName)) {
                continue;
            }
            Db::execute('DROP TABLE `' . $tableName . '`');
            $dropped[] = $tableName;
        }

        // 2. 删除菜单、配置、升级日志与模块记录（事务）
        $menuIds = $this->collectMenuIds($manifest['menus']);
        Db::startTrans();
        try {
            if (!empty($menuIds)) {
                SystemMenu::whereIn('id', $menuIds)->delete();
            }
            $configCount = SystemConfig::where('module', $module)->count();
            SystemConfig::where('module', $module)->delete();
            $groupCount = SystemConfigGroup::where('module', $module)->count();
            SystemConfigGroup::where('module', $module)->delete();
            try {
                Db::name('system_upgrade_log')->where('module', $module)->delete();
            } catch (\Throwable) {
                // 旧版本可能没有升级日志表，忽略即可
            }
            SystemModule::where('dir', $module)->delete();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw new LogicException('清理模块数据失败：' . $e->getMessage());
        }
        SystemConfig::clearConfigCache();

        // 3. 删除模块文件，失败仅记录，不影响已完成的数据清理
        $deletedFiles = [];
        $missingFiles = [];
        foreach ($manifest['files'] as $file) {
            $path = $this->resolveSafePath($file, $module);
            if ($path === '') {
                $missingFiles[] = $file;
                continue;
            }
            $this->deletePath($path);
            $deletedFiles[] = $file;
        }

        return [
            'module' => $module,
            'tables' => $dropped,
            'menus' => count($menuIds),
            'configs' => $configCount + $groupCount,
            'files' => $deletedFiles,
            'missing_files' => $missingFiles,
        ];
    }

    /**
     * 卸载预演：统计将被清理的数据表、菜单与配置数量（不执行任何删除）
     *
     * @param string $module
     * @return array
     * @throws LogicException
     */
    public function previewUninstall(string $module): array
    {
        $module = trim($module);
        if (in_array($module, self::PROTECTED_MODULES, true)) {
            throw new LogicException('系统核心模块禁止卸载');
        }
        $info = $this->getModuleFileInfo($module);
        if (!is_array($info) || ($info['keyword'] ?? '') !== $module) {
            throw new LogicException('info.php 不存在或模块标记与目录不一致');
        }
        $manifest = $this->normalizeUninstallManifest($module, $info['uninstall'] ?? null);
        $prefix = $this->getDbPrefix();

        $tables = [];
        foreach ($manifest['tables'] as $table) {
            $tableName = $this->resolveModuleTable($module, $table, $prefix);
            if ($this->tableExists($tableName)) {
                $tables[] = $tableName;
            }
        }
        $menuIds = $this->collectMenuIds($manifest['menus']);

        return [
            'module' => $module,
            'tables' => $tables,
            'menus' => count($menuIds),
            'configs' => SystemConfig::where('module', $module)->count()
                + SystemConfigGroup::where('module', $module)->count(),
            'files' => $manifest['files'],
        ];
    }

    /**
     * 校验并规范化卸载清单
     *
     * @param string $module
     * @param mixed $manifest
     * @return array
     * @throws LogicException
     */
    protected function normalizeUninstallManifest(string $module, mixed $manifest): array
    {
        if (!is_array($manifest)) {
            throw new LogicException('模块未在 info.php 中声明卸载清单（uninstall）');
        }
        $tables = $manifest['tables'] ?? [];
        if (!is_array($tables)) {
            throw new LogicException('卸载清单 tables 必须为数组');
        }
        foreach ($tables as $table) {
            if (!is_string($table) || trim($table) === '') {
                throw new LogicException('卸载清单 tables 存在非法表名');
            }
        }

        $menus = is_array($manifest['menus'] ?? null) ? $manifest['menus'] : [];
        $slot = (string)($menus['slot'] ?? '');
        $nodePrefix = (string)($menus['node_prefix'] ?? '');
        if ($slot !== '' && $slot !== 'content_' . $module) {
            throw new LogicException('菜单 slot 声明与模块不匹配');
        }
        if ($nodePrefix !== '' && $nodePrefix !== $module . '/') {
            throw new LogicException('菜单节点前缀声明与模块不匹配');
        }

        $configs = is_array($manifest['configs'] ?? null) ? $manifest['configs'] : [];
        if (!empty($configs) && (string)($configs['module'] ?? '') !== $module) {
            throw new LogicException('配置 module 声明与模块不匹配');
        }

        $files = $manifest['files'] ?? [];
        if (!is_array($files)) {
            throw new LogicException('卸载清单 files 必须为数组');
        }
        foreach ($files as $file) {
            if (!is_string($file) || trim($file) === '') {
                throw new LogicException('卸载清单 files 存在非法路径');
            }
        }

        return [
            'tables' => array_values(array_unique($tables)),
            'menus' => ['slot' => $slot, 'node_prefix' => $nodePrefix],
            'files' => array_values(array_unique($files)),
        ];
    }

    /**
     * 获取数据库表前缀
     *
     * @return string
     */
    protected function getDbPrefix(): string
    {
        try {
            return (string)$this->app->db->connect()->getConfig('prefix');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 校验表名：只允许当前模块前缀下的表，禁止系统表与跨模块表
     *
     * @param string $module
     * @param string $table
     * @param string $prefix
     * @return string
     * @throws LogicException
     */
    protected function resolveModuleTable(string $module, string $table, string $prefix): string
    {
        $table = trim($table);
        if ($table === '' || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            throw new LogicException('数据表名不合法：' . $table);
        }
        // 兼容安装 SQL 的 ha_ 占位写法
        if ($prefix !== '' && str_starts_with($table, 'ha_')) {
            $table = $prefix . substr($table, 3);
        }
        if ($prefix !== '' && !str_starts_with($table, $prefix)) {
            throw new LogicException('数据表不属于当前数据库前缀：' . $table);
        }
        $bare = $prefix !== '' ? substr($table, strlen($prefix)) : $table;
        if (!str_starts_with($bare, $module . '_')) {
            throw new LogicException("数据表 {$table} 不属于模块 {$module}");
        }
        if (preg_match('/^(system|user)_/', $bare)) {
            throw new LogicException('禁止删除系统或用户数据表：' . $table);
        }
        return $table;
    }

    /**
     * 判断数据表是否存在
     *
     * @param string $table
     * @return bool
     */
    protected function tableExists(string $table): bool
    {
        // 表名已通过 resolveModuleTable 校验，仅含字母数字下划线
        return !empty(Db::query("SHOW TABLES LIKE '" . $table . "'"));
    }

    /**
     * 收集待删除菜单 ID（含所有子菜单）
     *
     * @param array $menus
     * @return array
     */
    protected function collectMenuIds(array $menus): array
    {
        $slot = (string)($menus['slot'] ?? '');
        $nodePrefix = (string)($menus['node_prefix'] ?? '');
        $ids = [];
        if ($slot !== '') {
            $ids = array_merge($ids, SystemMenu::where('slot', $slot)->column('id'));
        }
        if ($nodePrefix !== '') {
            $ids = array_merge($ids, SystemMenu::where('node', 'like', $nodePrefix . '%')->column('id'));
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }
        // 递归补充子菜单
        while (true) {
            $children = array_map('intval', SystemMenu::whereIn('pid', $ids)->column('id'));
            $children = array_values(array_diff($children, $ids));
            if (empty($children)) {
                break;
            }
            $ids = array_merge($ids, $children);
        }
        return $ids;
    }

    /**
     * 校验文件路径，仅允许项目根目录下的 app/route/view/public 白名单范围
     *
     * @param string $relative
     * @param string $module
     * @return string 返回真实路径，不存在时返回空字符串
     * @throws LogicException
     */
    protected function resolveSafePath(string $relative, string $module): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || preg_match('#(^|/)\.\.(/|$)#', $relative)) {
            throw new LogicException('非法文件路径：' . $relative);
        }
        if (stripos($relative, $module) === false) {
            throw new LogicException('文件路径与模块不匹配：' . $relative);
        }
        $parts = explode('/', $relative);
        if (!in_array($parts[0], ['app', 'route', 'view', 'public'], true)) {
            throw new LogicException('文件路径不在允许的目录内：' . $relative);
        }
        if ($parts[0] === 'app' && count($parts) < 2) {
            throw new LogicException('禁止删除应用根目录：' . $relative);
        }
        $shared = [
            'app', 'app/home', 'app/admin', 'app/model', 'app/logic', 'app/command', 'app/service',
            'app/api', 'app/event', 'app/common', 'app/common/fun', 'app/common/install',
            'app/common/upgrade', 'route', 'public', 'public/static', 'view', 'view/home_style',
        ];
        if (in_array($relative, $shared, true)) {
            throw new LogicException('禁止删除公共目录：' . $relative);
        }

        $root = rtrim((string)$this->app->getRootPath(), '/\\');
        $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $real = realpath($full);
        if ($real === false) {
            return '';
        }
        $realRoot = realpath($root);
        if ($realRoot === false || !str_starts_with($real, $realRoot . DIRECTORY_SEPARATOR)) {
            throw new LogicException('文件路径越界：' . $relative);
        }
        return $real;
    }

    /**
     * 删除文件或目录（目录递归删除，软链接只删链接本身）
     *
     * @param string $path
     * @return void
     */
    protected function deletePath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isLink() || $item->isFile()) {
                @unlink($item->getPathname());
            } else {
                @rmdir($item->getPathname());
            }
        }
        @rmdir($path);
    }
}
