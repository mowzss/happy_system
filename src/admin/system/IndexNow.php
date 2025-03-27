<?php

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\logic\system\ConfigLogic;
use app\model\system\SystemConfigGroup;
use mowzs\lib\Forms;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\template\exception\TemplateNotFoundException;

class IndexNow extends BaseAdmin
{

    /**
     * 分组模型
     * @var SystemConfigGroup
     */
    protected SystemConfigGroup $groupModel;
    /**
     * @var
     */
    protected $list;

    public function __construct(App $app, SystemConfigGroup $configGroup)
    {
        parent::__construct($app);
        $this->groupModel = $configGroup;
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
        $this->list = $this->groupModel->where([
            'module' => 'p_index_now',
            'status' => 1
        ])->select();
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['group_id'])) {
                $this->error('group_id不能为空');
            }
            if (!empty($data['index_key'])) {
                file_put_contents(public_path() . $data['index_key'] . '.txt', $data['index_key']);
            }
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = implode(',', $value);
                }
            }
            if (ConfigLogic::instance()->saveConfig($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }


        //渲染页面
        try {
            return $this->fetch();
        } catch (TemplateNotFoundException $exception) {
            //模板不存在时 尝试读取公用模板
            return $this->fetch('common@/setting');
        }
    }

    /**
     * 获取设置表单
     * @auth true
     * @param int $group_id
     * @return string|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
    public function getForms(int $group_id = 0)
    {
        if (empty($group_id)) {
            $this->error('group_id 不能为空');
        }
        $data = ConfigLogic::instance()->getListByGroup($group_id);
        if (!empty($data)) {
            return Forms::instance(['action' => urls('index')])
                ->setInputs([['type' => 'hidden', 'name' => 'group_id', 'value' => $group_id]])
                ->render($data);
        }
        $this->error('暂无设置表单信息');
    }
}
