<?php
declare (strict_types=1);

namespace app\logic\user;

use app\model\user\UserAuth;
use happy\admin\libs\BaseLogic;
use happy\admin\libs\helper\NodeHelper;

/**
 * 权限组授权规则（节点树 + 保存）
 *
 * 职责：
 * 1. 读取 NodeHelper 的扁平节点（键形如 `模块.控制器/方法`）并组装为 3 层树：
 *    模块 → 控制器 → 方法（叶子）
 * 2. 把 UserAuth.nodes（JSON 数组）转换成 hash set，模板里用 isset 直接判断选中态，
 *    避免 foreach 里反复 in_array 的 O(n³)。
 * 3. 保存时过滤掉 is_auth=false 的节点（不可授权），以及非本节点树的脏数据。
 */
class AuthLogic extends BaseLogic
{
    /**
     * 3 层显式循环组装节点树
     *
     * NodeHelper::getMethods() 返回格式示例：
     *   lingqian.model/copy       => ['title'=>'复制模型', 'is_auth'=>true, ...]
     *   lingqian.model/index      => ['title'=>'内容列表', 'is_auth'=>true, ...]
     *   lingqian.model            => ['title'=>'', ...]    ← 控制器声明行
     *   lingqian.setting          => ['title'=>'模块设置', 'is_auth'=>false]
     *
     * 按 `.` 拆出顶层模块名，按 `/` 拆出中间控制器 + 叶子方法。
     * 不是通用 N 层树——项目里就是 3 层，显式循环比递归 + 引用传递简单得多。
     *
     * @param array $flat 来自 NodeHelper::getMethods() 的扁平数组
     * @return array 模块树，每个节点含 node/title/is_auth/is_login/is_menu/sub
     */
    public function buildTree(array $flat): array
    {
        $tree = [];

        foreach ($flat as $key => $meta) {
            // $key 形如  lingqian.model/index  或  lingqian.model
            $parts = explode('.', $key, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$moduleName, $rest] = $parts;

            // 确保模块层存在
            if (!isset($tree[$moduleName])) {
                $tree[$moduleName] = [
                    'node'  => $moduleName,
                    'title' => ucfirst($moduleName),
                    'is_auth'  => true,
                    'is_login' => true,
                    'is_menu'  => false,
                    'is_module'=> true,
                    'sub'   => [],
                ];
            }

            // 控制器 / 方法层：$rest 形如 model/index  或  model
            if (str_contains($rest, '/')) {
                [$controller, $method] = explode('/', $rest, 2);
                // 确保控制器层存在
                if (!isset($tree[$moduleName]['sub'][$controller])) {
                    $tree[$moduleName]['sub'][$controller] = [
                        'node'  => $moduleName . '.' . $controller,
                        'title' => $meta['title'] ?? ucfirst($controller),
                        'is_auth'  => true,
                        'is_login' => true,
                        'is_menu'  => false,
                        'is_controller' => true,
                        'sub'   => [],
                    ];
                }
                // 叶子方法
                $tree[$moduleName]['sub'][$controller]['sub'][$key] = [
                    'node'     => $key,
                    'title'    => $meta['title'] ?? ucfirst($method),
                    'is_auth'  => (bool)($meta['is_auth']  ?? true),
                    'is_login' => (bool)($meta['is_login'] ?? true),
                    'is_menu'  => (bool)($meta['is_menu']  ?? false),
                    'is_leaf'  => true,
                ];
            } else {
                // 仅控制器声明行（不带方法），用来补全控制器 title
                if (!isset($tree[$moduleName]['sub'][$rest])) {
                    $tree[$moduleName]['sub'][$rest] = [
                        'node'  => $key,
                        'title' => $meta['title'] ?? ucfirst($rest),
                        'is_auth'  => (bool)($meta['is_auth']  ?? true),
                        'is_login' => (bool)($meta['is_login'] ?? true),
                        'is_menu'  => (bool)($meta['is_menu']  ?? false),
                        'is_controller' => true,
                        'sub'   => [],
                    ];
                } elseif (!empty($meta['title'])) {
                    // 用声明行的 title 覆盖默认 ucfirst
                    $tree[$moduleName]['sub'][$rest]['title'] = $meta['title'];
                }
            }
        }

        // 按模块名排序 + 每个模块内控制器/方法按 node 排序（数组 key 自然序）
        ksort($tree);
        foreach ($tree as &$module) {
            ksort($module['sub']);
            foreach ($module['sub'] as &$controller) {
                ksort($controller['sub']);
            }
            unset($controller);
        }
        unset($module);

        return $tree;
    }

    /**
     * 把已选节点数组转成 hash map（模板里 isset $checkMap[$node] O(1) 判断选中态）
     *
     * @param array $nodes UserAuth.nodes 原始值（JSON 解码后的数组，可能是节点字符串或 assoc）
     * @return array
     */
    public function buildCheckMap(array $nodes): array
    {
        $map = [];
        foreach ($nodes as $item) {
            if (is_string($item)) {
                $map[$item] = true;
            } elseif (is_array($item) && isset($item['node'])) {
                $map[$item['node']] = true;
            }
        }
        return $map;
    }

    /**
     * 准备 rule 页面所有数据（树 + 组信息 + 已选 hash）
     *
     * @param int $id UserAuth 主键
     * @return array 包含 info / nodes / checkMap / flatMap
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function prepareForRule(int $id): array
    {
        /** @var UserAuth $info */
        $info = $this->app->make('app\model\user\UserAuth')->findOrEmpty($id);
        if ($info->isEmpty()) {
            throw new \RuntimeException('权限组不存在');
        }
        if (empty($info['is_authorize'])) {
            throw new \RuntimeException('用户组不可授权（is_authorize=0）');
        }

        $flat = NodeHelper::instance()->getMethods(true);
        $tree = $this->buildTree($flat);

        return [
            'info'     => $info,
            'tree'     => $tree,
            'checkMap' => $this->buildCheckMap($info['nodes'] ?? []),
        ];
    }

    /**
     * 保存授权节点
     *
     * @param int   $id    权限组 ID
     * @param array $nodes POST 提交的 nodes[] 值数组（原始表单数据）
     * @return bool
     * @throws \Throwable
     */
    public function save(int $id, array $nodes): bool
    {
        $flat = NodeHelper::instance()->getMethods(true);

        // 构建有效节点集合：只保留 is_auth=true 的（不可授权的节点一律过滤掉）
        $validNodes = [];
        foreach ($flat as $key => $meta) {
            if (!empty($meta['is_auth'])) {
                $validNodes[$key] = true;
            }
        }

        $cleaned = [];
        foreach ($nodes as $node) {
            $node = trim((string)$node);
            if ($node === '') continue;
            if (!isset($validNodes[$node])) continue;   // 不可授权 / 脏数据过滤
            $cleaned[] = $node;
        }

        $info = UserAuth::find($id);
        if (!$info) {
            throw new \RuntimeException('权限组不存在');
        }

        $info->nodes = array_values(array_unique($cleaned));
        return $info->save();
    }
}
