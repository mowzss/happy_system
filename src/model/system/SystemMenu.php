<?php
declare (strict_types=1);

namespace app\model\system;

use mowzs\lib\helper\AuthHelper;
use mowzs\lib\helper\DataHelper;
use mowzs\lib\Model;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class SystemMenu extends Model
{
    protected $table_fields = [
        'id' => [
            'type' => 'int',
            'name' => 'ID',
        ]
    ];

    /**
     * 查询后
     * @param $model
     * @return \think\Model
     */
    public static function onAfterRead($model): \think\Model
    {
        if (empty($model['node']) || $model['node'] == '#') {
            $model['type'] = 0;
            return $model;
        }
        $model['type'] = 1;
        $model['openType'] = '_component';
        $model['href'] = $model['class'] == 1
            ? url($model['node'], $model['params'] ?: [])->build()
            : $model['node'];
        return $model;
    }

    /**
     * 获取系统菜单
     * @param array $where
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getMenuTree(array $where = ['status' => 1]): array
    {
        $menu = self::where($where)->order('list', 'desc')->select()->toArray();
        // 判断是否为超管账号，如果是，则不进行权限过滤
        if (!AuthHelper::instance()->isAuthAdmin()) {
            $userNodes = AuthHelper::instance()->getUserNodesModule();

            // 过滤函数或循环处理
            $filteredMenu = array_filter($menu, function ($item) use ($userNodes) {
                // 如果 node 是 '#' 或者空字符串，则直接保留该项目
                if ($item['node'] === '#' || $item['node'] === '') {
                    return true;
                }
                // 否则，仅当 node 存在于 userNodes 中时保留该项目
                return in_array($item['node'], $userNodes);
            });
            // 保存 过滤无权限的菜单
            $menu = array_values($filteredMenu); // 重置键值以保持数组连续性
        }
        return DataHelper::instance()->arrToTree($menu);
    }

    /**
     * @param array $where
     * @return array
     */
    public static function getMenuForm(array $where = ['status' => 1]): array
    {
        return ['0' => '[顶级菜单]'] + DataHelper::instance()->transformArray(DataHelper::instance()->arrToTable(self::where($where)->column('title,pid,id', 'id')));
    }
}
