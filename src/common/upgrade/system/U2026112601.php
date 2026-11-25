<?php

namespace app\common\upgrade\system;

use app\logic\system\ConfigLogic;
use think\facade\Db;

class U2026112601
{
    private const CONFIG_NAME = 'site_subtitle';

    public function run(): void
    {
        $anchor = Db::name('system_config')
            ->where('name', 'seo_title')
            ->where('module', 'system')
            ->find();

        if (!$anchor) {
            throw new \RuntimeException('未找到系统配置「网站标题」(seo_title)，无法新增网站副标题');
        }

        $groupId = (int)$anchor['group_id'];

        Db::transaction(function () use ($groupId): void {
            $configId = $this->ensureConfig($groupId);
            $this->placeAfterSeoTitle($groupId, $configId);
        });

        ConfigLogic::clearConfigCache();
    }

    protected function ensureConfig(int $groupId): int
    {
        $now = time();
        $exists = Db::name('system_config')->where('name', self::CONFIG_NAME)->find();
        $meta = [
            'type'     => 'text',
            'title'    => '网站副标题',
            'group_id' => $groupId,
            'help'     => '显示在网站顶部信息条等位置的副标题。',
            'module'   => 'system',
            'status'   => 1,
        ];

        if ($exists) {
            $update = [];
            foreach ($meta as $field => $value) {
                if (($exists[$field] ?? null) != $value) {
                    $update[$field] = $value;
                }
            }
            if (!empty($update)) {
                $update['update_time'] = $now;
                Db::name('system_config')->where('id', $exists['id'])->update($update);
            }

            return (int)$exists['id'];
        }

        return (int)Db::name('system_config')->insertGetId([
            'name'        => self::CONFIG_NAME,
            'type'        => $meta['type'],
            'title'       => $meta['title'],
            'group_id'    => $groupId,
            'options'     => '',
            'help'        => $meta['help'],
            'value'       => '',
            'extend'      => '',
            'list'        => 0,
            'module'      => $meta['module'],
            'status'      => $meta['status'],
            'create_time' => $now,
            'update_time' => $now,
        ]);
    }

    protected function placeAfterSeoTitle(int $groupId, int $configId): void
    {
        $rows = Db::name('system_config')
            ->where('group_id', $groupId)
            ->field('id,name,list')
            ->order(['list' => 'desc', 'id' => 'asc'])
            ->select()
            ->toArray();

        $orderedIds = [];
        $anchorFound = false;
        foreach ($rows as $row) {
            if ((int)$row['id'] === $configId) {
                continue;
            }

            $orderedIds[] = (int)$row['id'];
            if ($row['name'] === 'seo_title') {
                $orderedIds[] = $configId;
                $anchorFound = true;
            }
        }

        if (!$anchorFound) {
            throw new \RuntimeException('网站副标题所属分组中未找到「网站标题」(seo_title)');
        }

        $currentIds = array_map('intval', array_column($rows, 'id'));
        if ($currentIds === $orderedIds) {
            return;
        }

        // 同组配置常出现相同 list，统一重排可确保副标题紧跟标题且重复执行不再变化。
        $lists = array_column($rows, 'list', 'id');
        $list = count($orderedIds);
        foreach ($orderedIds as $id) {
            if ((int)$lists[$id] !== $list) {
                Db::name('system_config')->where('id', $id)->update(['list' => $list]);
            }
            $list--;
        }
    }
}
