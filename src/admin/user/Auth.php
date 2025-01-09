<?php
declare (strict_types=1);

namespace app\admin\user;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\user\UserAuth;
use mowzs\lib\helper\NodeHelper;
use think\App;
use think\Exception;

/**
 * 权限组设置
 */
class Auth extends BaseAdmin
{
    use CrudTrait;

    /**
     * @var array|mixed
     */
    protected mixed $nodes;
    /**
     * @var UserAuth|array|mixed|\think\Model
     */
    protected mixed $info;

    public function __construct(App $app, UserAuth $userAuth)
    {
        parent::__construct($app);
        $this->model = $userAuth;
        $this->setParams();
    }

    /**
     * @return void
     */
    protected function setParams(): void
    {
        $this->tables = [
            'fields' => [
                [
                    'field' => 'id',
                    'title' => 'ID',
                    'width' => 80,
                    'sort' => true,
                ],
                [
                    'field' => 'title',
                    'title' => '名称',
                    'edit' => 'text',
                ], [
                    'field' => 'desc',
                    'title' => '描述',
                    'edit' => 'textarea',
                ]
            ],
            'top_button' => [

            ],
            'right_button' => [
                [
                    'event' => '',
                    'type' => 'data-open',
                    'url' => urls('rule', ['id' => '__id__']),
                    'name' => '授权',
                    'class' => '',//默认包含 layui-btn layui-btn-xs
                ],
                ['event' => 'edit'],
                ['event' => 'del'],
            ]

        ];
        $this->forms = [
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'title',
                    'label' => '名称',
                    'required' => true
                ], [
                    'type' => 'textarea',
                    'name' => 'desc',
                    'label' => '介绍',
                ], [
                    'type' => 'radio',
                    'name' => 'is_authorize',
                    'label' => '管理权限',
                    'options' => [
                        '0' => '用户组（无权限）',
                        '1' => '管理组（有权限）'
                    ]
                ]
            ]
        ];
    }

    /**
     * 权限组授权
     * @auth true
     * @return string
     * @throws Exception
     */
    public function rule(): string
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('id不能为空');
        }
        $this->nodes = $this->buildHierarchicalArray(NodeHelper::instance()->getMethods(true));
        $this->info = $this->model->findOrEmpty($id);
        if ($this->info->isEmpty()) {
            $this->error('权限组不存在');
        }
        if (empty($this->info['is_authorize'])) {
            $this->error('用户组不可授权');
        }
        if ($this->request->isPost()) {
            $data = request()->post();
            if (empty($data['id'])) {
                $this->error('ID不能为空');
            }
            if (!isset($data['nodes'])) {
                $this->error('参数错误');
            }
            if ($this->model->update($data)) {
                $this->success('保存成功');
            }
        }
        return $this->fetch();
    }

    /**
     * 将扁平的关联数组转换为具有层级结构的数组，并保留原始键。
     *
     * @param array $flatArray 扁平的关联数组，键为路径，值为包含节点信息的数组。
     * @return array 具有层级结构的数组，每个节点包含 'node' 键以记录原始键。
     */
    protected function buildHierarchicalArray(array $flatArray): array
    {
        $hierarchicalArray = [];

        foreach ($flatArray as $key => $value) {
            // 首先以.分割键名为主要层级
            $mainParts = explode('.', $key);
            $currentLevel = &$hierarchicalArray;

            // 递归处理每个部分，创建层级结构
            $this->processParts($mainParts, $value, $currentLevel, $key);
        }

        return $hierarchicalArray;
    }

    /**
     * 递归处理键的部分，创建层级结构。
     *
     * @param array $parts 当前键的部分数组。
     * @param array $value 当前节点的信息。
     * @param array &$currentLevel 当前层级的引用。
     * @param string $originalKey 原始键名。
     */
    protected function processParts(array $parts, array $value, &$currentLevel, string $originalKey)
    {
        if (empty($parts)) {
            return;
        }

        $part = array_shift($parts);

        // 如果当前部分包含斜杠，进一步分割
        if (strpos($part, '/') !== false) {
            $subParts = explode('/', $part);
            $part = array_shift($subParts);
            $remainingKey = implode('/', $subParts);

            if (!isset($currentLevel[$part])) {
                $currentLevel[$part] = [
                    'title' => isset($value['title']) ? $value['title'] : ucfirst(str_replace('_', ' ', $part)),
                    'node' => $part,
                    'sub' => []
                ];
            }

            // 递归处理剩余的斜杠分割部分
            $this->processParts([$remainingKey], $value, $currentLevel[$part]['sub'], $originalKey);
        } else {
            if (!isset($currentLevel[$part])) {
                $currentLevel[$part] = [
                    'title' => isset($value['title']) ? $value['title'] : ucfirst(str_replace('_', ' ', $part)),
                    'node' => $part,
                    'sub' => []
                ];
            }

            // 如果还有剩余部分，继续递归处理
            if (!empty($parts)) {
                $this->processParts($parts, $value, $currentLevel[$part]['sub'], $originalKey);
            } else {
                // 处理最后一个部分，即实际的节点
                $currentLevel[$part] = [
                    'title' => isset($value['title']) ? $value['title'] : ucfirst(str_replace('_', ' ', $part)),
                    'is_login' => isset($value['is_login']) ? $value['is_login'] : true,
                    'is_menu' => isset($value['is_menu']) ? $value['is_menu'] : false,
                    'is_auth' => isset($value['is_auth']) ? $value['is_auth'] : true,
                    'node' => $originalKey
                ];
            }
        }
    }
}
