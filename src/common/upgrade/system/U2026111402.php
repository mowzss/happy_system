<?php

namespace app\common\upgrade\system;

use think\facade\Db;

/**
 * 2026-11-14 后台二次验证（TOTP）数据结构升级
 *
 * 1) user_info 表补三个字段：totp_secret / totp_enabled / totp_bound_at
 * 2) 后台菜单新增「二次验证」，挂到 system_menu 中 slot=system_ext 的父级（系统功能）下
 *    - 早期手工插入挂在「用户信息」分组下的记录，这里会被迁移到 system_ext，不会重复插入
 *
 * 幂等：字段已存在跳过；菜单按 node 判断，存在则只纠正归属，可重复执行。
 * 表前缀由 Db::name() / Db::getConfig('prefix') 自动适配当前项目，不写死单站前缀。
 */
class U2026111402
{
    /**
     * 升级入口
     * @return void
     */
    public function run(): void
    {
        $this->ensureUserColumns();
        $this->ensureMenu();
    }

    /**
     * 补齐 user_info 表的 TOTP 字段
     * @return void
     */
    protected function ensureUserColumns(): void
    {
        // 用连接配置取当前项目前缀（Db::getConfig 读的是 database.prefix，
        // 而实际前缀在 database.connections.mysql.prefix，所以走 Connection）
        $table = (string)Db::connect()->getConfig('prefix') . 'user_info';

        $columns = [];
        foreach (Db::query("SHOW COLUMNS FROM `{$table}`") as $row) {
            $columns[] = $row['Field'] ?? ($row['field'] ?? '');
        }

        $definitions = [
            'totp_secret'   => "ADD COLUMN `totp_secret` VARCHAR(64) NULL DEFAULT NULL COMMENT 'TOTP 密钥(Base32)'",
            'totp_enabled'  => "ADD COLUMN `totp_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '二次验证是否启用'",
            'totp_bound_at' => "ADD COLUMN `totp_bound_at` INT(11) NULL DEFAULT NULL COMMENT '二次验证绑定时间'",
        ];

        foreach ($definitions as $name => $ddl) {
            if (in_array($name, $columns, true)) {
                continue;
            }
            Db::execute("ALTER TABLE `{$table}` {$ddl}");
        }
    }

    /**
     * 确保「二次验证」菜单挂在 system_ext 父级下
     * @return void
     */
    protected function ensureMenu(): void
    {
        $parent = Db::name('system_menu')->where('slot', 'system_ext')->find();
        if (empty($parent)) {
            // 兜底：按标题找「系统功能」
            $parent = Db::name('system_menu')->where('title', '系统功能')->find();
        }
        if (empty($parent)) {
            throw new \RuntimeException('未找到 slot=system_ext 的菜单父级（系统功能），无法挂载二次验证菜单');
        }
        $parentId = (int)$parent['id'];

        $menu = Db::name('system_menu')->where('node', 'user/totp/index')->find();

        if ($menu) {
            $update = [];
            if ((int)$menu['pid'] !== $parentId) {
                $update['pid'] = $parentId;
            }
            if (($menu['title'] ?? '') !== '二次验证') {
                $update['title'] = '二次验证';
            }
            if ((int)($menu['status'] ?? 0) !== 1) {
                $update['status'] = 1;
            }
            if (!empty($update)) {
                Db::name('system_menu')->where('id', $menu['id'])->update($update);
            }
            return;
        }

        Db::name('system_menu')->insert([
            'pid'      => $parentId,
            'title'    => '二次验证',
            'icon'     => 'layui-icon layui-icon-vercode',
            'slot'     => null,
            'node'     => 'user/totp/index',
            'params'   => '',
            'class'    => 1,
            'list'     => 0,
            'status'   => 1,
        ]);
    }
}