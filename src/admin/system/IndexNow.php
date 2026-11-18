<?php
declare (strict_types=1);


namespace app\admin\system;

use think\App;
use app\common\controllers\BaseAdmin;
use app\common\traits\SettingTrait;
use app\model\system\SystemConfigGroup;
use think\db\exception\DbException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

/**
 * IndexNow 推送设置
 */
class IndexNow extends BaseAdmin
{
    use SettingTrait;

    /**
     * 分组模型
     * @var SystemConfigGroup
     */
    protected SystemConfigGroup $groupModel;

    public function __construct(App $app, SystemConfigGroup $configGroup)
    {
        parent::__construct($app);
        $this->groupModel = $configGroup;
        // IndexNow 使用固定的 p_index_now 模块分组。
        // 在构造函数赋值而非类内声明，避免与 Trait 同名属性默认值冲突。
        $this->settingModule = 'p_index_now';
    }

    /**
     * 设置
     * @auth true
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index(): string
    {
        if ($this->request->isPost()) {
            $this->saveSetting($this->request->post());
        }

        $this->assign('list', $this->loadSettingGroups());
        return $this->renderSettingPage();
    }

    /**
     * 保存前副作用：写入 verification 文件、规范化数组值
     *
     * @param array $data
     * @return void
     */
    protected function beforeSaveSetting(array &$data): void
    {
        if (!empty($data['index_key'])) {
            $key = (string)$data['index_key'];
            // 安全校验：IndexNow 密钥为纯字母数字串，禁止任何路径分隔符或点号，
            // 防止 "../../etc/x" 之类输入通过路径拼接写到站点之外。
            if (!preg_match('/^[A-Za-z0-9_-]{5,128}$/', $key) || $key !== basename($key)) {
                $this->error('推送秘钥格式不合法（仅允许字母、数字、下划线、连字符，长度 5-128）');
            }
            $file = public_path() . $key . '.txt';
            if (file_put_contents($file, $key) === false) {
                $this->error('验证文件写入失败，请检查 public 目录权限');
            }
        }

        // layui 多选等组件提交数组，落库前转为逗号分隔字符串
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = implode(',', $value);
            }
        }
    }

    /**
     * 手动推送
     * @return string
     */
    public function push()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
        }
        return $this->fetch();
    }
}
