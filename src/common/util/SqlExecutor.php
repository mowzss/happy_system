<?php
declare(strict_types=1);

namespace app\common\util;

use think\facade\Db;

class SqlExecutor extends UtilBase
{
    /**
     * sql文件路径
     * @var string
     */
    protected string $filePath;
    /**
     * 数据库前缀
     * @var string
     */
    protected string $prefix;

    protected string $sys_prefix = 'ha_';

    /**
     * 执行SQL文件
     * @param string $filePath
     * @param string $type 支持安装和升级
     * @param string $prefix
     * @return void
     * @throws \Exception
     */
    public function execute(string $filePath, string $type = 'install', string $prefix = ''): void
    {
        if ($type == 'install') {//安装
            $path = '/common/install/';
        } else if ($type == 'update') {//升级
            $path = '/common/upgrade/';
        }
        $this->filePath = $this->app->getBasePath() . $path . $filePath;
        // 如果传入的前缀为空，则尝试从配置中获取
        $this->setPrefix($prefix);
        if (!file_exists($this->filePath)) {
            throw new \Exception("SQL file does not exist: " . $this->filePath);
        }
        $sqlContent = file_get_contents($this->filePath);
        $sqlWithNewPrefix = str_replace($this->sys_prefix, $this->prefix, $sqlContent);
        // 分割成多个单独的SQL语句
        $sqlStatements = $this->splitSqlIntoStatements($sqlWithNewPrefix);

        // 执行每个SQL语句
        foreach ($sqlStatements as $sql) {
            if (trim($sql) !== '') {
                try {
                    Db::execute($sql);
                } catch (\Exception $e) {
                    // 处理错误（例如记录日志）
                    throw new \Exception("Failed to execute SQL statement: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * 将SQL内容分割成多个独立的SQL语句
     *
     * @param string $sqlContent SQL内容
     * @return array SQL语句数组
     */
    protected function splitSqlIntoStatements(string $sqlContent): array
    {
        // 使用分号作为分隔符来分割SQL语句
        return preg_split("/;\s*/", $sqlContent, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * 设置前缀
     * @param string $prefix
     * @return void
     */
    protected function setPrefix(string $prefix): void
    {
        if (empty($prefix)) {
            $config = $this->app->config->get('database');
            $connection = $config['default'] ?? 'mysql';
            $this->prefix = $config['connections'][$connection]['prefix'] ?? '';
        } else {
            $this->prefix = $prefix;
        }
    }
}
