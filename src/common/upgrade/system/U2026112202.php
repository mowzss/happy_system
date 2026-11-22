<?php

namespace app\common\upgrade\system;

use think\facade\Db;

/**
 * 2026-11-22 新增「OpenGraph 默认图片」设置项
 *
 * 背景：happy-libs 的 SeoHeadMetaHelper 在生成 og:image / twitter:image /
 * JSON-LD 图片时，优先读取系统配置 system.site_logo_image，缺省才回退到
 * square_logo。但历史 install.sql 与各站点库中从未定义该配置项，导致后台
 * 无法设置社交分享首图。
 *
 * 本升级文件幂等新增一个 image 类型配置项：
 *   - name:       site_logo_image（与 SeoHeadMetaHelper 读取键一致）
 *   - title:      OpenGraph默认图片
 *   - group_id:   基础设置分组（按 title + module 查找，不写死 id）
 *   - value:      留空（留空时沿用回退逻辑，不覆盖现有 square_logo 行为）
 *
 * 幂等：配置项已存在时只纠正 type / group_id / status / help，不覆盖 value，
 *       可重复执行。表前缀由 Db::name() 自动适配当前项目。
 */
class U2026112202
{
    /**
     * 配置项名称（必须与 SeoHeadMetaHelper 读取键保持一致）
     */
    private const CONFIG_NAME = 'site_logo_image';

    public function run(): void
    {
        $groupId = $this->ensureGroupId();
        $this->ensureConfig($groupId);
    }

    /**
     * 定位「基础设置」分组 id
     * 优先按 title + module 查找；找不到时取 module=system 的第一个分组兜底。
     *
     * @return int
     */
    protected function ensureGroupId(): int
    {
        $group = Db::name('system_config_group')
            ->where('title', '基础设置')
            ->where('module', 'system')
            ->find();

        if (!$group) {
            $group = Db::name('system_config_group')
                ->where('module', 'system')
                ->order('id', 'asc')
                ->find();
        }

        if (!$group) {
            throw new \RuntimeException('未找到 system 模块的配置分组，无法新增 OpenGraph 默认图片设置项');
        }

        return (int)$group['id'];
    }

    /**
     * 确保配置项存在且元数据正确；已存在时不覆盖 value
     *
     * @param int $groupId
     * @return void
     */
    protected function ensureConfig(int $groupId): void
    {
        $now = time();
        $exists = Db::name('system_config')->where('name', self::CONFIG_NAME)->find();

        $meta = [
            'type'     => 'image',
            'title'    => 'OpenGraph默认图片',
            'group_id' => $groupId,
            'help'     => '社交分享（微信/QQ/微博等）抓取页面时展示的默认图片，用于 og:image 与 twitter:image。留空则回退使用「方形LOGO」。建议尺寸 1200×630。',
            'module'   => 'system',
            'status'   => 1,
        ];

        if ($exists) {
            // 仅纠正元数据，绝不覆盖用户已设置的 value
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
            return;
        }

        Db::name('system_config')->insert([
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
}
