<?php

namespace app\logic\system;


use app\model\system\SystemConfig;
use mowzs\lib\BaseLogic;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Cache;

class ConfigLogic extends BaseLogic
{

    /**
     * 清理重复的配置项
     *
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function clearDuplicates(): void
    {
        // 1. 按 module 分组清理
        $this->clearByField('module');
        // 2. 按 group_id 分组清理
        $this->clearByField('group_id');
    }

    /**
     * 根据指定字段清理重复的配置项
     *
     * @param string $field 分组字段 (module 或 group_id)
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function clearByField(string $field): void
    {
        // 获取所有不同的分组值
        $groups = SystemConfig::group($field)->column($field);

        foreach ($groups as $group) {
            // 如果分组值为空，跳过
            if (empty($group)) {
                continue;
            }

            // 找到该分组下所有 name 相同的记录，并按 update_time 降序排序
            $names = SystemConfig::where($field, $group)
                ->group('name')
                ->having('COUNT(name) > 1')  // 只选择有重复 name 的记录
                ->column('name');

            foreach ($names as $name) {
                // 查询该分组下 name 相同的记录，并按 update_time 降序排序
                $records = SystemConfig::where([$field => $group, 'name' => $name])
                    ->order('update_time', 'desc')
                    ->select();

                // 如果没有重复记录，跳过
                if ($records->count() <= 1) {
                    continue;
                }

                // 保留最新的记录，删除其他重复的记录
                $latestRecord = $records->first();
                $duplicates = $records->filter(function ($record) use ($latestRecord) {
                    return $record['id'] !== $latestRecord['id'];
                });

                // 删除重复的记录
                foreach ($duplicates as $duplicate) {
                    SystemConfig::destroy($duplicate['id']);
                }
            }
        }
    }

    /**
     * @param $gid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getListByGroup($gid): array
    {
        return SystemConfig::where('group_id', $gid)->where(['status' => 1])->order(['list' => 'desc', 'id' => 'desc'])->select()->each(function ($item) {
            $item['label'] = $item['title'];
        })->toArray();
    }

    /**
     * 保存配置项
     *
     * @param array $data 提交的数据
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function saveConfig(array $data): bool
    {
        try {
            // 使用 \think\db::transaction() 包裹事务逻辑
            return self::transaction(function () use ($data) {
                // 清理重复的配置项
                $this->clearDuplicates();

                // 遍历提交的数据并保存
                foreach ($data as $key => $value) {
                    if ($key === 'group_id') {
                        continue;  // 跳过 group_id，因为它不是配置项的 name
                    }

                    // 查找是否存在该 name 的配置项
                    $config = SystemConfig::where(['name' => $key, 'group_id' => $data['group_id']])->findOrEmpty();
                    // 如果存在，更新现有记录
                    if (!$config->isEmpty()) {
                        $config->save(['value' => $value]);
                    }
                }
                $this->clearConfigCache();//清理缓存
                $this->loadAllConfigsToCache();//加载缓存
                return true;
            });
        } catch (\Exception $e) {
            throw new Exception('保存配置失败: ' . $e->getMessage());
        }
    }


    /**
     * 根据名称获取配置值，名称可以是单个名称或 "module.name" 的形式
     * 如果 name 为空，则返回所有配置数据
     * @param string|null $name 配置名称或 "module.name" 或 null
     * @param mixed|null $default 默认值
     * @return mixed 返回配置值或默认值，或所有配置数据
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getConfigValue(?string $name = null, mixed $default = null): mixed
    {
        // 确保所有配置已加载到缓存
        $this->loadAllConfigsToCache();

        // 构建缓存键
        $cacheKey = 'all_configs';

        // 从缓存中获取所有配置
        $allConfigs = Cache::get($cacheKey);

        // 如果 name 为空，返回所有配置数据
        if (is_null($name)) {
            return $allConfigs;
        }

        // 解析模块和名称
        $parts = explode('.', $name, 2);
        if (count($parts) === 1) {
            // 如果只有名称，默认从 system 模块查找
            $module = 'system';
            $name = $parts[0];
        } else {
            // 如果提供了模块.名称的形式
            list($module, $name) = $parts;
        }

        // 尝试从缓存中获取特定模块和名称的配置值
        $value = isset($allConfigs[$module][$name]) ? $allConfigs[$module][$name] : $default;
        return $value;
    }

    /**
     * 将所有配置项加载到缓存中
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function loadAllConfigsToCache(): void
    {
        // 构建缓存键
        $cacheKey = 'all_configs';

        // 如果缓存中没有，则从数据库加载并设置缓存
        if (!Cache::has($cacheKey)) {
            $configs = SystemConfig::field('name, module, value')
                ->select()
                ->toArray();

            // 转换为所需的结构
            $formattedConfigs = [];
            foreach ($configs as $config) {
                $formattedConfigs[$config['module']][$config['name']] = $config['value'];
            }
            // 设置缓存，可以指定过期时间，这里假设7200秒
            Cache::set($cacheKey, $formattedConfigs, 7200);
        }
    }

    /**
     * 清除配置缓存，用于当配置发生变更时调用
     * @return void
     */
    public static function clearConfigCache(): void
    {
        Cache::delete('all_configs');
    }
}
