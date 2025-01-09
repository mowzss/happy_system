<?php
declare (strict_types=1);

namespace app\model\user;

use mowzs\lib\Model;

class UserInfo extends Model
{
    /**
     * @param $value
     * @return false|string
     */
    public function getLastTimeAttr($value): bool|string
    {
        return date('Y-m-d H:i:s', $value ?: 0);
    }

    /**
     * 群组关联
     * @return \think\model\relation\HasOne
     */
    public function usergroup(): \think\model\relation\HasOne
    {
        return $this->hasOne(UserGroup::class, 'id', 'group_id')->bind(['group_name' => 'name']);
    }
}
