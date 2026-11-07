<?php
declare (strict_types=1);


namespace app\admin\system;

use think\App;
use app\common\traits\CrudTrait;
use app\logic\system\ModuleLogic;
use app\model\system\SystemModule;
use app\common\controllers\BaseAdmin;

/**
 * 系统模块管理
 */
class Module extends BaseAdmin
{
    use CrudTrait;
    
    protected array $modules;
    
    public function __construct(App $app, SystemModule $module)
    {
        parent::__construct($app);
        $this->model = $module;
        $this->setParams();
    }
    
    /**
     * @return void
     */
    protected function setParams(): void
    {
        $this->tables = [
            'title' => '系统模块管理',
            'fields' => [
                [
                    'field' => 'id',
                    'title' => 'ID',
                    'width' => 80,
                    'sort' => true,
                ],
                [
                    'field' => 'title',
                    'title' => '模块名称',
                    'align' => 'content',
                ], [
                    'field' => 'dir',
                    'title' => '模块目录',
                ], [
                    'field' => 'type_name',
                    'title' => '类型',
                ], [
                    'field' => 'status',
                    'title' => '状态',
                    'templet' => 'switch',
                ], [
                    'field' => 'create_time',
                    'title' => '创建时间',
                ],
            ],
            
            'top_button' => [
                [
                    'event' => 'add',
                    'name' => '安装本地模块',
                ],
                ['event' => 'del', 'name' => '卸载'],
            ],
            'right_button' => [
                ['event' => 'edit', 'name' => '编辑'],
                ['event' => 'del', 'name' => '卸载'],
            ],
        ];
        $this->forms = ['fields' => [
            [
                'type' => 'text',
                'name' => 'title',
                'label' => '模块名称',
            ], [
                'type' => 'text',
                'label' => 'dir',
                'title' => '模块目录',
            ], [
                'type' => 'radio',
                'label' => 'type',
                'title' => '类型',
                'options' => [
                    1 => '模块',
                    2 => '插件',
                ],
            ],
        ]];
        $this->search = ['id#=#id', 'title#=#title', 'dir#=#module', 'status#=#status', 'create_time#between#create_time', 'update_time#between#update_time'];
    }
    
    /**
     * 添加本地模块
     * @return string
     */
    public function add(): string
    {
        $this->modules = ModuleLogic::instance()->notInstalledModules();
        if ($this->request->isPost()) {
            $module = $this->request->post('module');
            if (empty($module)) {
                $this->error("提交信息有误请检查！");
            }
            try {
                ModuleLogic::instance()->install($module);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success('安装成功!');
        }
        
        return $this->fetch();
    }

    /**
     * 卸载模块
     * 按模块 info.php 中的 uninstall 声明清理数据表、菜单、配置与模块文件
     *
     * @auth true
     * @return void
     */
    public function delete(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求错误');
        }
        $ids = $this->request->param('ids');
        if (is_null($ids) || $ids === '') {
            $this->error('id不能为空');
        }
        $ids = is_array($ids) ? $ids : [(int)$ids];
        $records = SystemModule::whereIn('id', $ids)->select();
        if ($records->isEmpty()) {
            $this->error('记录不存在');
        }
        $done = [];
        foreach ($records as $record) {
            try {
                $result = ModuleLogic::instance()->uninstall((string)$record['dir']);
                $done[] = $result['module'];
            } catch (\Throwable $e) {
                $this->error('卸载失败：' . $e->getMessage());
            }
        }
        $this->success('卸载成功：' . implode('、', $done));
    }
}
