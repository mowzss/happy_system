<?php

namespace app\common\upgrade\system;

use think\facade\Db;

/**
 * 2026-11-13 蜘蛛统计后台菜单
 * 在 slot='spider' 父节点下插入两个只读子菜单：
 *   - system/spiderDate/index    每日统计
 *   - system/spiderHourly/index  每小时统计
 *
 * 幂等：node 已存在则跳过，可重复执行。
 * 自动适配表前缀（Db::name 内部读取 database.connections.mysql.prefix）。
 */
class U2026111301
{
    public function run(): void
    {
        // 1. 定位蜘蛛日志父级菜单（slot='spider' 是框架约定）
        $parent = Db::name('system_menu')
            ->where('slot', 'spider')
            ->where('status', 1)
            ->find();

        if (!$parent) {
            // 父级不存在（例如老版本未带 slot），尝试兜底 node='system/spider/index'
            $parent = Db::name('system_menu')
                ->where('node', 'system/spider/index')
                ->where('status', 1)
                ->find();
        }

        if (!$parent) {
            throw new \RuntimeException('蜘蛛日志父级菜单未找到（slot=spider）');
        }

        $pid = (int)$parent['id'];

        // 2. 准备插入项
        $items = [
            [
                'title' => '每日统计',
                'node'  => 'system/spiderDate/index',
                'list'  => 50,
            ],
            [
                'title' => '每小时统计',
                'node'  => 'system/spiderHourly/index',
                'list'  => 60,
            ],
        ];

        foreach ($items as $item) {
            $exists = Db::name('system_menu')
                ->where('node', $item['node'])
                ->find();

            if ($exists) {
                continue;
            }

            Db::name('system_menu')->insert([
                'pid'    => $pid,
                'title'  => $item['title'],
                'icon'   => '',
                'slot'   => '',
                'node'   => $item['node'],
                'params' => '',
                'class'  => 1,
                'list'   => $item['list'],
                'status' => 1,
            ]);
        }
    }
}
