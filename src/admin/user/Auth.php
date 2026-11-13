<?php
declare (strict_types=1);


namespace app\admin\user;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\user\UserAuth;
use think\App;
use think\Exception;

/**
 * 权限组设置
 */
class Auth extends BaseAdmin
{
    use CrudTrait;

    protected mixed $info;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new UserAuth();
        $this->setParams();
    }

    protected function setParams(): void
    {
        $this->tables = [
            'fields' => [
                ['field' => 'id', 'title' => 'ID', 'width' => 80, 'sort' => true],
                ['field' => 'title', 'title' => '名称', 'edit' => 'text'],
                ['field' => 'desc',  'title' => '描述', 'edit' => 'textarea'],
            ],
            'top_button' => [],
            'right_button' => [
                [
                    'event' => '',
                    'type'  => 'data-menu-open',
                    'url'   => urls('rule', ['id' => '__id__']),
                    'name'  => '节点授权',
                ],
                ['event' => 'edit'],
                ['event' => 'del'],
            ],
        ];
        $this->forms = [
            'fields' => [
                ['type' => 'text',    'name' => 'title',        'label' => '名称', 'required' => true],
                ['type' => 'textarea','name' => 'desc',         'label' => '介绍'],
                ['type' => 'radio',   'name' => 'is_authorize', 'label' => '管理权限',
                 'options' => ['0' => '用户组（无权限）', '1' => '管理组（有权限）']],
            ],
        ];
    }

    /**
     * 权限组节点授权
     * 树构建 + 已选 hash + save 过滤全部下沉至 AuthLogic
     *
     * @auth true
     * @return string
     * @throws Exception
     */
    public function rule(): string
    {
        $id = (int)$this->request->param('id', 0);
        if ($id <= 0) {
            $this->error('id 不能为空');
        }

        /** @var \app\logic\user\AuthLogic $logic */
        $logic = $this->app->make('app\logic\user\AuthLogic');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (empty($data['id'])) {
                $this->error('ID 不能为空');
            }
            if (!isset($data['nodes'])) {
                $this->error('参数错误');
            }
            try {
                $logic->save((int)$data['id'], (array)$data['nodes']);
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());
            }
            $this->success('保存成功');
        }

        try {
            extract($logic->prepareForRule($id), EXTR_SKIP);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        } catch (\Throwable $e) {
            $this->error('系统错误：' . $e->getMessage());
        }

        $this->assign('info',     $info);
        $this->assign('tree',     $tree);
        $this->assign('checkMap', $checkMap);

        return $this->fetch();
    }
}
