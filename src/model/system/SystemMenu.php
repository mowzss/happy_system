<?php
declare (strict_types=1);

namespace app\model\system;

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
        return DataHelper::instance()->arrToTree(self::where($where)->order('list', 'desc')->select()->toArray());
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