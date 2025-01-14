<?php
declare (strict_types=1);

namespace app\common\util;

use app\common\util\table\TableStructures;
use InvalidArgumentException;
use PDO;
use think\db\exception\DbException;
use think\facade\Db;
use think\facade\Log;

class TableCreatorUtil extends UtilBase
{
    /**
     * 根据类型返回需要创建的表及其字段
     * @param string $name 表名称
     * @param int|string $type 类型
     * @return array
     * @throws \Exception
     */
    protected function getTablesToCreate(string $name, int|string $type): array
    {
        $tables = [];
        $typeFields = TableStructures::getTypeFields();
        if (!isset($typeFields[$type])) {
            throw new \Exception("Unsupported type: {$type}");
        }
        $tables[$name] = $typeFields[$type];
        return $tables;
    }

    /**
     * 获取数据库前缀
     * @param $table_name
     * @return string 数据库前缀
     */
    protected function getTableName($table_name): string
    {
        $config = $this->app->config->get('database');
        $connection = $config['default'] ?? 'mysql';
        $prefix = $config['connections'][$connection]['prefix'] ?? '';
        return $prefix . $table_name;
    }

    /**
     * 创建或修改表
     * @param string $tableName 表名
     * @param array $fields 字段定义数组
     * @param bool $create 是否为创建新表（默认为 false，表示修改现有表）
     * @return array 返回操作结果
     */
    protected function alterTable(string $tableName, array $fields, bool $create = false): array
    {
        try {
            // 确保表名包含前缀
            $tableName = $this->getTableName($tableName);
            // 初始化 SQL 语句
            $sql = $create ? "CREATE TABLE IF NOT EXISTS `{$tableName}` (" : "ALTER TABLE `{$tableName}`";
            // 存储字段定义
            $definitions = [];
            if ($create) {
                // 如果是创建新表，首先添加主键定义
                $idField = $fields['id'] ?? [
                    'type' => 'BIGINT(20) UNSIGNED',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                    'null' => false,
                    'default' => null,
                    'comment' => '主键ID',
                ];
                $definitions[] = "  `id` " . $this->buildFieldDefinition($idField) . " PRIMARY KEY";
            }
            // 添加字段定义
            foreach ($fields as $fieldName => $fieldOptions) {
                if ($fieldName !== 'id' || !$create) {
                    $action = $create ? '' : 'ADD COLUMN ';
                    $definitions[] = "{$action}`{$fieldName}` " . $this->buildFieldDefinition($fieldOptions);
                }
            }
            // 将所有定义组合成一个字符串
            $sql .= implode(",\n", $definitions);
            if ($create) {
                // 如果是创建新表，添加表的注释和引擎信息
                $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='内容表';";
            } else {
                $sql .= ";";
            }
            // 执行 SQL 语句
            Db::execute($sql);
            return [
                'success' => true,
                'message' => "Table '{$tableName}' " . ($create ? 'created' : 'modified') . " successfully.",
            ];
        } catch (\Exception $e) {
            Log::error("Error " . ($create ? 'creating' : 'modifying') . " table: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error " . ($create ? 'creating' : 'modifying') . " table: " . $e->getMessage(),
            ];
        }
    }

    /**
     * 复制表结构
     * @param string $sourceTable 源表名
     * @param string $targetTable 目标表名
     * @return array 返回结果
     */
    public function copyTableStructure(string $sourceTable, string $targetTable): array
    {
        try {
            $sourceTable = $this->getTableName($sourceTable);
            $targetTable = $this->getTableName($targetTable);
            
            // 获取源表的创建语句
            $showCreateTable = Db::query("SHOW CREATE TABLE `{$sourceTable}`");

            if (empty($showCreateTable)) {
                throw new \Exception("Source table '{$sourceTable}' does not exist.");
            }

            // 提取CREATE TABLE语句
            $createTableSql = $showCreateTable[0]['Create Table'];

            // 替换表名为目标表名
            $newCreateTableSql = str_replace("CREATE TABLE `{$sourceTable}`", "CREATE TABLE `{$targetTable}`", $createTableSql);

            // 执行修改后的SQL语句
            Db::execute($newCreateTableSql);

            return [
                'success' => true,
                'message' => "Table structure copied from '{$sourceTable}' to '{$targetTable}' successfully.",
            ];
        } catch (\Exception $e) {
            Log::error("Error copying table structure: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error copying table structure: " . $e->getMessage(),
            ];
        }
    }

    /**
     * 复制表中的特定记录并根据指定字段进行修改后重新插入
     * @param string $tableName 表名
     * @param array $sourceConditions 原始记录的查询条件
     * @param array $newValues 新记录中需要修改或新增的字段及其值
     * @return array 返回结果
     */
    public function copyTableRows(string $tableName, array $sourceConditions, array $newValues): array
    {
        try {
            // 查询所有符合条件的源记录
            $sourceDataList = Db::name($tableName)->where($sourceConditions)->select()->toArray();

            if (empty($sourceDataList)) {
                throw new DbException("Source records with conditions '" . json_encode($sourceConditions) . "' do not exist.");
            }

            $insertedCount = 0;

            foreach ($sourceDataList as $sourceData) {
                // 移除主键以避免冲突（假设主键是自增的id）
                unset($sourceData['id']);

                // 合并原数据与新的字段值
                $insertData = array_merge($sourceData, $newValues);

                // 插入新记录
                Db::name($tableName)->insert($insertData);
                $insertedCount++;
            }

            return [
                'success' => true,
                'message' => "{$insertedCount} records copied and modified successfully.",
            ];
        } catch (DbException $e) {
            Log::error("Error copying table rows: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error copying table rows: " . $e->getMessage(),
            ];
        }
    }

    /**
     * 构建字段定义
     * @param array $fieldOptions 字段选项
     * @param PDO|null $pdo PDO 实例，用于转义字符串
     * @return string 返回字段定义的 SQL 语句部分
     */
    protected function buildFieldDefinition(array $fieldOptions, ?PDO $pdo = null): string
    {
        $definition = $fieldOptions['type'];

        // 处理字符类型（如 VARCHAR, CHAR 等），添加长度
        if (in_array(strtoupper($fieldOptions['type']), ['VARCHAR', 'CHAR'])) {
            if (isset($fieldOptions['length']) && $fieldOptions['length'] > 0) { // 确保长度有效
                $definition .= "({$fieldOptions['length']})";
            }
        }

        // 处理整数类型（如 TINYINT, SMALLINT, MEDIUMINT, INT, BIGINT 等），处理无符号和自动递增
        if (in_array(strtoupper($fieldOptions['type']), ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT'])) {
            if (isset($fieldOptions['unsigned']) && ($fieldOptions['unsigned'] || $fieldOptions['unsigned'] === 1)) {
                $definition .= ' UNSIGNED';
            }
            if (isset($fieldOptions['auto_increment']) && ($fieldOptions['auto_increment'] || $fieldOptions['auto_increment'] === 1)) {
                $definition .= ' AUTO_INCREMENT';
            }
        }

        // 处理 NOT NULL 选项
        if (isset($fieldOptions['null']) && ($fieldOptions['null'] === false || $fieldOptions['null'] === 0)) {
            $definition .= ' NOT NULL';
        } else {
            $definition .= ' NULL'; // 如果未明确指定 NOT NULL，则默认为 NULL
        }

        // 检查是否为不允许有默认值的数据类型
        $disallowedDefaultTypes = ['TEXT', 'BLOB', 'GEOMETRY', 'JSON'];
        $hasDefaultValue = isset($fieldOptions['default']);

        if ($hasDefaultValue && in_array(strtoupper($fieldOptions['type']), $disallowedDefaultTypes)) {
            throw new InvalidArgumentException("Column of type {$fieldOptions['type']} cannot have a default value.");
        }

        // 处理默认值
        if ($hasDefaultValue) {
            // 根据默认值的类型进行处理
            if (is_string($fieldOptions['default'])) {
                if ($pdo !== null) {
                    $escapedDefault = $pdo->quote($fieldOptions['default']);
                    $definition .= " DEFAULT {$escapedDefault}";
                } else {
                    // 如果没有 PDO 实例，使用 addslashes 作为备选方案（不推荐）
                    $escapedDefault = addslashes($fieldOptions['default']);
                    $definition .= " DEFAULT '{$escapedDefault}'";
                }
            } elseif (is_null($fieldOptions['default'])) {
                $definition .= ' DEFAULT NULL';
            } elseif (is_bool($fieldOptions['default'])) {
                $definition .= ' DEFAULT ' . ($fieldOptions['default'] ? 'TRUE' : 'FALSE');
            } elseif (is_numeric($fieldOptions['default'])) {
                $definition .= ' DEFAULT ' . $fieldOptions['default'];
            } else {
                throw new InvalidArgumentException('Unsupported default value type.');
            }
        }

        // 处理字段注释
        if (isset($fieldOptions['comment']) && !empty($fieldOptions['comment'])) {
            // 使用 PDO 的 quote 方法来安全地转义注释中的单引号
            if ($pdo !== null) {
                $escapedComment = str_replace(['\'', '\\'], ['\\\'', '\\\\'], $fieldOptions['comment']);
                $definition .= " COMMENT '{$escapedComment}'";
            } else {
                // 如果没有 PDO 实例，使用 addslashes 作为备选方案（不推荐）
                $escapedComment = addslashes($fieldOptions['comment']);
                $definition .= " COMMENT '{$escapedComment}'";
            }
        }

        return $definition;
    }

    /**
     * 创建表
     * @param string $name 表名称
     * @param int|string $type 表类型
     * @return array 返回操作结果
     */
    public function createTable(string $name, int|string $type): array
    {
        try {
            // 根据类型决定创建哪些表
            $tablesToCreate = $this->getTablesToCreate($name, $type);

            $results = [];
            foreach ($tablesToCreate as $tableName => $fields) {
                try {
                    // 调用 alterTable 方法创建表
                    $result = $this->alterTable($tableName, $fields, true);
                    if ($result['success']) {
                        $results[] = [
                            'table' => $tableName,
                            'status' => 'success',
                            'message' => "Table '{$tableName}' created successfully.",
                            'errors' => [], // 没有错误
                        ];
                    } else {
                        $results[] = [
                            'table' => $tableName,
                            'status' => 'error',
                            'message' => $result['message'],
                            'errors' => [$result['message']], // 收集错误信息
                        ];
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'table' => $tableName,
                        'status' => 'error',
                        'message' => "Error creating table '{$tableName}': " . $e->getMessage(),
                        'errors' => [$e->getMessage()], // 收集异常信息
                    ];
                    Log::error("Error creating table '{$tableName}': " . $e->getMessage());
                }
            }

            // 检查是否有任何表创建失败
            $allSuccess = !array_filter($results, function ($result) {
                return $result['status'] === 'error';
            });

            return [
                'success' => $allSuccess,
                'message' => $allSuccess ? 'All tables created successfully.' : 'Some tables failed to create.',
                'data' => $results,
            ];

        } catch (\Exception $e) {
            Log::error("Error creating table: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error creating table: " . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * 为已有的表添加字段
     * @param string $tableName 表名
     * @param array $fields 新字段的定义数组
     * @return array 返回操作结果
     */
    public function addFields(string $tableName, array $fields): array
    {
        return $this->alterTable($tableName, $fields, false);
    }

    /**
     * 从已有的表中删除字段
     * @param string $tableName 表名
     * @param array $fieldNames 要删除的字段名称数组
     * @return array 返回操作结果
     */
    public function removeFields(string $tableName, array $fieldNames): array
    {
        try {
            $sql = "ALTER TABLE `" . $this->getTableName($tableName) . "`";
            // 存储字段删除语句
            $definitions = [];
            foreach ($fieldNames as $fieldName) {
                $definitions[] = "DROP COLUMN `{$fieldName}`";
            }
            // 将所有字段删除语句组合成一个字符串
            $sql .= implode(", ", $definitions) . ";";
            // 执行 SQL 语句
            Db::execute($sql);
            return [
                'success' => true,
                'message' => "Fields removed from table '{$tableName}' successfully.",
            ];
        } catch (\Exception $e) {
            Log::error("Error removing fields from table: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error removing fields from table: " . $e->getMessage(),
            ];
        }
    }

    /**
     * 为已有的表添加索引
     * @param string $tableName 表名
     * @param array $indexes 索引定义数组，键为索引名称，值为要索引的字段名称或字段数组
     * @return array 返回操作结果
     */
    public function addIndexes(string $tableName, array $indexes): array
    {
        try {
            $sql = "ALTER TABLE `" . $this->getTableName($tableName) . "`";
            // 存储索引定义
            $indexDefinitions = [];
            foreach ($indexes as $indexName => $fields) {
                if (is_array($fields)) {
                    // 如果是复合索引，使用多个字段
                    $fieldsStr = implode(", ", array_map(function ($field) {
                        return "`{$field}`";
                    }, $fields));
                    $indexDefinitions[] = "ADD INDEX `{$indexName}` ({$fieldsStr})";
                } else {
                    // 单个字段索引
                    $indexDefinitions[] = "ADD INDEX `{$indexName}` (`{$fields}`)";
                }
            }
            // 将所有索引定义组合成一个字符串
            $sql .= implode(", ", $indexDefinitions) . ";";
            // 执行 SQL 语句
            Db::execute($sql);
            return [
                'success' => true,
                'message' => "Indexes added to table '{$tableName}' successfully.",
            ];
        } catch (\Exception $e) {
            Log::error("Error adding indexes to table: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error adding indexes to table: " . $e->getMessage(),
            ];
        }
    }

    /**
     * 从已有的表中删除索引
     * @param string $tableName 表名
     * @param array $indexNames 要删除的索引名称数组
     * @return array 返回操作结果
     */
    public function removeIndexes(string $tableName, array $indexNames): array
    {
        try {
            $sql = "ALTER TABLE `" . $this->getTableName($tableName) . "`";
            // 存储索引删除语句
            $indexDrops = [];
            foreach ($indexNames as $indexName) {
                $indexDrops[] = "DROP INDEX `{$indexName}`";
            }
            // 将所有索引删除语句组合成一个字符串
            $sql .= implode(", ", $indexDrops) . ";";
            // 执行 SQL 语句
            Db::execute($sql);
            return [
                'success' => true,
                'message' => "Indexes removed from table '{$tableName}' successfully.",
            ];
        } catch (\Exception $e) {
            Log::error("Error removing indexes from table: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error removing indexes from table: " . $e->getMessage(),
            ];
        }
    }

    /**
     * 删除表
     * @param string $tableName 表名
     * @return array 返回操作结果
     */
    public function dropTable(string $tableName): array
    {
        try {
            // 确保表名包含前缀
            $tableName = $this->getTableName($tableName);
            // 使用单引号包裹表名模式，并转义特殊字符
            $escapedTableName = addslashes($tableName);
            // 使用单引号包裹表名模式，并转义特殊字符
            $escapedTableName = addslashes($tableName);
            $sql = "SHOW TABLES LIKE '{$escapedTableName}'";
            // 检查表是否存在
            $exists = Db::query($sql);
            if (empty($exists)) {
                return [
                    'success' => false,
                    'message' => "Table '{$tableName}' does not exist.",
                ];
            }
            // 转义表名以防止 SQL 注入
            $escapedTableName = '`' . str_replace('`', '``', $tableName) . '`';

            // 构建 SQL 语句
            $sql = "DROP TABLE IF EXISTS {$escapedTableName};";
            // 执行 SQL 语句
            Db::execute($sql);
            return [
                'success' => true,
                'message' => "Table '{$tableName}' dropped successfully.",
            ];
        } catch (\Exception $e) {
            Log::error("Error dropping table: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error dropping table: " . $e->getMessage(),
            ];
        }
    }

    /**
     * 修改已有的字段
     * @param string $tableName 表名
     * @param string $fieldName 字段名称
     * @param array $newFieldOptions 新字段选项
     * @return array 返回操作结果
     */
    public function modifyField(string $tableName, string $fieldName, array $newFieldOptions): array
    {
        try {
            // 确保表名包含前缀
            $tableName = $this->getTableName($tableName);
            // 构建字段定义
            $fieldDefinition = $this->buildFieldDefinition($newFieldOptions);
            // 构建 SQL 语句
            $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$fieldName}` {$fieldDefinition};";
            // 执行 SQL 语句
            Db::execute($sql);
            return [
                'success' => true,
                'message' => "Field '{$fieldName}' in table '{$tableName}' modified successfully.",
            ];
        } catch (\Exception $e) {
            Log::error("Error modifying field '{$fieldName}' in table '{$tableName}': " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error modifying field '{$fieldName}' in table '{$tableName}': " . $e->getMessage(),
            ];
        }
    }
}
