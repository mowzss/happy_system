<?php
declare (strict_types=1);

namespace app\common\traits;

use app\logic\system\ConfigLogic;
use app\model\system\SystemConfigGroup;
use happy\admin\libs\Exception\FormsException;
use happy\admin\libs\Forms;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\template\exception\TemplateNotFoundException;

/**
 * 后台设置页公共逻辑
 *
 * 统一「系统设置 / CMS 模块设置 / IndexNow 等独立设置」三处重复的：
 * 分组列表加载、分组归属校验、表单按需渲染、配置保存与缓存刷新。
 *
 * 关键约定：
 * 1. 配置保存统一走 ConfigLogic::saveConfig()，它清理 all_system_configs 缓存，
 *    保证 sys_config() 立即读到新值。禁止改用 SystemConfig::saveConfig()
 *    （后者清理的是无人消费的 all_configs，会导致配置 2 小时内不生效）。
 * 2. $settingModule 非空时，getForms 与保存都会校验 group_id 是否属于该模块，
 *    防止跨模块越权读写。
 */
trait SettingTrait
{
    /**
     * 当前设置所属模块名。留空表示不做模块归属校验（系统全局设置）。
     * @var string
     */
    protected string $settingModule = '';

    /**
     * 分组查询条件。子类可覆盖（如系统设置用 sys_show=1）。
     * @var array
     */
    protected array $settingGroupWhere = ['status' => 1];

    /**
     * 模板变量赋值方式：true 用 $this->assign，false 用 $this 属性注入。
     * 统一使用 assign，避免两种风格混用。
     */

    /**
     * 加载分组列表，分组为空时自动创建默认分组并重新查询。
     *
     * @return \think\Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function loadSettingGroups(): \think\Collection
    {
        $where = $this->settingGroupWhere;
        if ($this->settingModule !== '') {
            $where['module'] = $this->settingModule;
        }

        $list = SystemConfigGroup::where($where)->select();
        if ($list->isEmpty() && $this->settingModule !== '') {
            // 模块设置：自动补建"基本设置"分组
            SystemConfigGroup::create([
                'title' => '基本设置',
                'module' => $this->settingModule,
            ]);
            // 修复：创建后必须重新查询，否则本次渲染的 tab 仍为空
            $list = SystemConfigGroup::where($where)->select();
        }
        return $list;
    }

    /**
     * 渲染设置页面（模板不存在时降级到公共模板）
     *
     * @return string
     */
    protected function renderSettingPage(): string
    {
        try {
            return $this->fetch();
        } catch (TemplateNotFoundException $exception) {
            return $this->fetch('common@/setting');
        }
    }

    /**
     * 获取设置表单（供前端按分组 AJAX 调用）
     *
     * @param int $group_id
     * @return string|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws FormsException
     * @throws Exception
     */
    public function getForms(int $group_id = 0)
    {
        if (empty($group_id)) {
            $this->error('group_id 不能为空');
        }
        $this->checkGroupOwner($group_id);

        $data = ConfigLogic::instance()->getListByGroup($group_id);
        if (!empty($data)) {
            return Forms::instance(['action' => urls('index')])
                ->setInputs([['type' => 'hidden', 'name' => 'group_id', 'value' => $group_id]])
                ->render($data);
        }
        $this->error('暂无设置表单信息');
    }

    /**
     * 保存配置（统一入口，修复缓存刷新）
     *
     * @param array $data POST 数据
     * @return void
     * @throws \happy\admin\libs\Exception\LogicException
     */
    protected function saveSetting(array $data): void
    {
        if (empty($data['group_id'])) {
            $this->error('group_id不能为空');
        }
        $this->checkGroupOwner((int)$data['group_id']);

        // 保存前的业务副作用（邮件测试、favicon 生成等），由子类覆写
        $this->beforeSaveSetting($data);

        // 统一走 ConfigLogic：其 saveConfig 清理 all_system_configs（sys_config 实际读取的缓存）
        if (ConfigLogic::instance()->saveConfig($data)) {
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 保存前钩子，默认空实现。子类覆写以处理各自副作用。
     *
     * @param array $data
     * @return void
     */
    protected function beforeSaveSetting(array &$data): void
    {
    }

    /**
     * 校验分组归属，防止跨模块越权读写。
     *
     * @param int $group_id
     * @return void
     */
    protected function checkGroupOwner(int $group_id): void
    {
        if ($this->settingModule === '') {
            return;
        }
        $owner = SystemConfigGroup::where('id', $group_id)->value('module');
        if ($owner === null || $owner !== $this->settingModule) {
            $this->error('分组不存在或无权访问');
        }
    }
}
