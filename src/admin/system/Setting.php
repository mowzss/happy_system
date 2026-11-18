<?php
declare (strict_types=1);


namespace app\admin\system;

use think\App;
use app\logic\system\ConfigLogic;
use app\model\system\SystemConfig;
use PHPMailer\PHPMailer\Exception;
use think\db\exception\DbException;
use app\common\controllers\BaseAdmin;
use app\common\traits\SettingTrait;
use app\model\system\SystemConfigGroup;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统参数设置
 */
class Setting extends BaseAdmin
{
    use SettingTrait;

    /**
     * 当前模型
     * @var SystemConfig
     */
    protected SystemConfig $model;
    /**
     * 分组模型
     * @var SystemConfigGroup
     */
    protected SystemConfigGroup $groupModel;

    public function __construct(App $app, SystemConfig $config, SystemConfigGroup $configGroup)
    {
        parent::__construct($app);
        $this->model = $config;
        $this->groupModel = $configGroup;
        // 系统设置只展示标记了 sys_show 的分组。
        // 注意：不能在类里直接声明与 Trait 同名的属性（PHP 要求默认值完全一致），
        // 因此在构造函数中赋值覆盖。
        $this->settingGroupWhere = ['sys_show' => 1, 'status' => 1];
    }

    /**
     * 设置
     * @auth true
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException|\happy\admin\libs\Exception\LogicException
     */
    public function index(): string
    {
        if ($this->request->isPost()) {
            $this->saveSetting($this->request->post());
        }

        $this->assign('list', $this->loadSettingGroups());
        return $this->fetch();
    }

    /**
     * 保存前副作用：邮件测试与站点图标重建
     *
     * @param array $data
     * @return void
     */
    protected function beforeSaveSetting(array &$data): void
    {
        if (!empty($data['mail_test']) && !empty($data['mail_send_user'])) {
            try {
                send_email($data['mail_send_user'], '测试邮件', '您申请的测试邮件');
            } catch (Exception $e) {
                $this->error('测试邮件发送失败:' . $e->getMessage());
            }
        }
        if (!empty($data['square_logo'])) {
            $this->app->console->call('admin:favicon');
        }
    }
}
