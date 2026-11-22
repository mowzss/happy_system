<?php

namespace app\common\upgrade\system;

use think\facade\Db;

/**
 * 2026-11-22 操作日志自动记录能力兜底升级
 *
 * 背景：本次实现了后台写操作的自动记录，写入依赖 ha_{prefix}_system_operation_log 表。
 * 该表在新版 install.sql 中已定义，但早期站点可能尚未创建；若表缺失，
 * 自动记录会被 OperationLogHelper 的异常隔离静默，表现为「日志始终为空」。
 *
 * 本升级文件不新增、不修改任何已有字段（现有 uid/node/desc/ip/user_agent/create_time
 * 足以承载操作日志），只做两件幂等的事：
 *  1) 表不存在时按 install.sql 的定义补建；
 *  2) 补齐 uid / create_time 两个查询索引（列表搜索与时间范围统计依赖）。
 *
 * 幂等：表与索引存在则全部跳过，可重复执行。
 * 表前缀由 Db::connect()->getConfig('prefix') 读取，不写死单站前缀。
 */
class U2026112201
{
    /** 与 install.sql 保持一致的索引名 */
    private const INDEX_UID  = 'system_operation_log_admin_id_index';
    private const INDEX_TIME = 'system_operation_log_create_time_index';

    /**
     * 升级入口
     * @return void
     */
    public function run(): void
    {
        $prefix = (string)Db::connect()->getConfig('prefix');
        $table  = $prefix . 'system_operation_log';

        if (!$this->tableExists($table)) {
            $this->createTable($table);
        }

        $this->ensureIndexes($table);
    }

    /**
     * 判断表是否存在
     * @param string $table
     * @return bool
     */
    protected function tableExists(string $table): bool
    {
        $rows = Db::query(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
        return !empty($rows);
    }

    /**
     * 按安装 SQL 补建操作日志表
     * @param string $table
     * @return void
     */
    protected function createTable(string $table): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$table}` (
    `id`          int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `uid`         int(10) unsigned NOT NULL COMMENT '管理员ID',
    `node`        varchar(255)     NOT NULL COMMENT '操作节点',
    `desc`        text COMMENT '操作描述',
    `ip`          varchar(45)      NOT NULL COMMENT '操作时的IP地址',
    `user_agent`  text COMMENT '操作时的User-Agent信息',
    `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间戳',
    PRIMARY KEY (`id`),
    KEY `system_operation_log_admin_id_index` (`uid`),
    KEY `system_operation_log_create_time_index` (`create_time`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '系统操作日志表'
SQL;
        Db::execute($sql);
    }

    /**
     * 补齐查询索引（已存在则跳过）
     * @param string $table
     * @return void
     */
    protected function ensureIndexes(string $table): void
    {
        $existing = [];
        foreach (Db::query("SHOW INDEX FROM `{$table}`") as $row) {
            $existing[] = $row['Key_name'] ?? ($row['key_name'] ?? '');
        }

        $definitions = [
            self::INDEX_UID  => "ADD INDEX `" . self::INDEX_UID . "` (`uid`)",
            self::INDEX_TIME => "ADD INDEX `" . self::INDEX_TIME . "` (`create_time`)",
        ];

        foreach ($definitions as $name => $ddl) {
            if (in_array($name, $existing, true)) {
                continue;
            }
            Db::execute("ALTER TABLE `{$table}` {$ddl}");
        }
    }
}
