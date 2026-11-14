<?php

namespace app\common\upgrade\system;

use think\facade\Db;

/**
 * 2026-11-14 举报反馈通知设置
 * 1) 新增设置分组「通知管理」，并在系统核心设置中显示（sys_show=1）
 * 2) 该分组下新增「反馈通知目标邮箱」字段 feedback_notify_emails
 *
 * 幂等：分组与字段均已存在则跳过，可重复执行。
 * 表前缀由 Db::name() 读取 database.connections.mysql.prefix 自动处理。
 */
class U2026111401
{
    public function run(): void
    {
        $groupId = $this->ensureGroup();
        $this->ensureConfig($groupId);
    }

    /**
     * 确保「通知管理」分组存在
     * @return int
     */
    protected function ensureGroup(): int
    {
        $group = Db::name('system_config_group')
            ->where('title', '通知管理')
            ->where('module', 'system')
            ->find();

        if ($group) {
            return (int)$group['id'];
        }

        return (int)Db::name('system_config_group')->insertGetId([
            'title'    => '通知管理',
            'sys_show' => 1,
            'module'   => 'system',
            'status'   => 1,
        ]);
    }

    /**
     * 确保「反馈通知目标邮箱」字段存在
     * @param int $groupId
     * @return void
     */
    protected function ensureConfig(int $groupId): void
    {
        $exists = Db::name('system_config')->where('name', 'feedback_notify_emails')->find();

        if ($exists) {
            if ((int)$exists['group_id'] !== $groupId) {
                Db::name('system_config')->where('id', $exists['id'])->update(['group_id' => $groupId]);
            }
            return;
        }

        Db::name('system_config')->insert([
            'name'        => 'feedback_notify_emails',
            'type'        => 'text',
            'title'       => '反馈通知目标邮箱',
            'group_id'    => $groupId,
            'options'     => '',
            'help'        => '收到新的举报反馈时发送给这些邮箱，多个用英文逗号分隔；留空则不发送通知。',
            'value'       => '',
            'extend'      => '',
            'list'        => 100,
            'module'      => 'system',
            'status'      => 1,
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }
}
