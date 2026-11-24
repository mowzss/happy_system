<?php


namespace app\logic\system;

use think\facade\Cache;
use happy\admin\libs\BaseLogic;
use app\model\system\SystemConfig;
use think\db\exception\DbException;
use think\db\exception\DataNotFoundException;
use happy\admin\libs\Exception\LogicException;
use think\db\exception\ModelNotFoundException;

class ConfigLogic extends BaseLogic
{
    
    /**
     * @param $gid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getListByGroup($gid): array
    {
        return SystemConfig::where('group_id', $gid)->where(['status' => 1])->order(['list' => 'desc', 'id' => 'asc'])->select()->each(function ($item) {
            $item['label'] = $item['title'];
        })->toArray();
    }
    
    /**
     * 保存配置项
     *
     * @param array $data 提交的数据
     * @return bool
     * @throws LogicException
     */
    public function saveConfig(array $data): bool
    {
        try {
            // 使用 \think\db::transaction() 包裹事务逻辑
            return $this->transaction(function () use ($data) {
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
                $this->clearConfigCache();     //清理缓存
                $this->loadAllConfigsToCache();//加载缓存
                return true;
            });
        } catch (\Exception $e) {
            throw new LogicException('保存配置失败: ' . $e->getMessage());
        }
    }
    
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
     * 清除配置缓存，用于当配置发生变更时调用
     * @return void
     */
    public static function clearConfigCache(): void
    {
        Cache::delete('all_system_configs');
    }
    
    /**
     * 将所有配置项加载到缓存中
     * @return mixed
     * @throws \Throwable
     */
    public function loadAllConfigsToCache(): mixed
    {
        // 构建缓存键
        $cacheKey = 'all_system_configs';
        // 如果缓存中没有，则从数据库加载并设置缓存
        return Cache::remember($cacheKey, function () {
            $configs = SystemConfig::field('name, module, value')
                ->select()
                ->toArray();
            // 转换为所需的结构
            $formattedConfigs = [];
            foreach ($configs as $config) {
                $formattedConfigs[$config['module']][$config['name']] = $config['value'];
            }
            return $formattedConfigs;
        }, 7200);
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
     * 根据名称获取配置值，名称可以是单个名称或 "module.name" 的形式
     * 如果 name 为空，则返回所有配置数据
     * @param string|null $name 配置名称或 "module.name" 或 null
     * @param mixed|null $default 默认值
     * @return mixed 返回配置值或默认值，或所有配置数据
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException|\Throwable
     */
    public function getConfigValue(?string $name = null, mixed $default = null): mixed
    {
        // 从缓存中获取所有配置
        $allConfigs = $this->loadAllConfigsToCache();
        
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
        } elseif (!empty($parts)) {
            // 如果提供了模块.名称的形式
            [$module, $name] = $parts;
        }
        
        // 尝试从缓存中获取特定模块和名称的配置值
        return $allConfigs[$module][$name] ?? $default;
    }

    /**
     * 允许输出给前端（Vue 等）的公开配置白名单。
     *
     * 安全约定：默认拒绝。只有显式列入本白名单的键才会对外输出，
     * 数据库密码、JWT 密钥、AES 密钥、OSS/七牛/短信 AK/SK、SMTP 密码、
     * 免密发布 Token、IndexNow key、OAuth appsecret 等敏感项一律不包含。
     * 结构：module => [name, ...]
     */
    private const PUBLIC_CONFIG_WHITELIST = [
        'system' => [
            // 站点基础信息
            'site_name', 'site_domain', 'site_wap_domain', 'site_logo', 'site_logo_image',
            'square_logo', 'copyright', 'icp_number', 'wa_number', 'posters_image',
            // SEO
            'seo_title', 'seo_keywords', 'seo_description',
            // 存储对外域名（不含任何 AK/SK/Secret）
            'storage_driver', 'static_upload', 'static_prefix', 'static_version',
            'oss_domain', 'qiniu_domain',
            // 手机版开关（供前端决定是否走 wap 域名/响应式）
            'is_wap_domain', 'is_wap_domain_dump', 'is_wap_dump_type', 'is_open_site',
            // 会员功能开关（前端据此决定是否展示注册入口等）
            'is_reg', 'is_reg_image_code', 'is_reg_sms_code', 'is_login_image_code',
        ],
    ];

    /**
     * 获取可安全输出给前端的公开配置。
     *
     * 采用白名单机制（默认拒绝），并对已知的键做类型规整：
     * - static_version 强制为字符串，避免前导零丢失；
     * - 开关类配置转为 int，便于前端判断；
     * - 空值保留为空字符串，保证字段稳定存在。
     *
     * 返回结构：['system' => ['site_name' => ..., ...], ...]
     *
     * @return array
     * @throws \Throwable
     */
    public function getPublicConfig(): array
    {
        $allConfigs = $this->loadAllConfigsToCache();
        $result = [];

        foreach (self::PUBLIC_CONFIG_WHITELIST as $module => $names) {
            if (!isset($allConfigs[$module])) {
                continue;
            }
            foreach ($names as $name) {
                if (!array_key_exists($name, $allConfigs[$module])) {
                    continue;
                }
                $value = $allConfigs[$module][$name];
                // 开关类与版本号规整为字符串/整型，避免 JSON 数字精度问题
                if (in_array($name, ['static_version', 'static_prefix'], true)) {
                    $value = (string)$value;
                } elseif (str_starts_with($name, 'is_')) {
                    $value = (int)$value;
                }
                $result[$module][$name] = $value;
            }
        }

        return $result;
    }
}
