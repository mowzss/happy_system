<?php
declare(strict_types=1);

namespace app\logic\user;

use app\model\user\UserInfo;
use mowzs\lib\BaseLogic;

class UserLogic extends BaseLogic
{
    /**
     * 获取用户信息
     * @param $uid
     * @param $hide_field
     * @return array
     */
    public function getUserInfoById($uid = '', $hide_field = []): array
    {
        if (empty($uid)) {
            return [];
        }
        $data = UserInfo::with(['usergroup'])->findOrEmpty($uid)->toArray();
        return $this->hideField($data, $hide_field);
    }

    /**
     * @param int|string $uid
     * @param false|string $field
     * @param array $hide_field
     * @return array|mixed
     */
    public function getUserField(int|string $uid, false|string $field = false, array $hide_field = []): mixed
    {
        if (empty($field)) {
            return $this->getUserInfoById($uid, $hide_field);
        }
        return $this->getUserInfoById($uid, $hide_field)[$field];
    }

    /**
     * 隐藏字段
     * @param array $data
     * @param mixed $hide_field
     * @return array
     */
    protected function hideField(array $data, mixed $hide_field): array
    {
        if (empty($hide_field)) {
            $hide_field = ['password', 'mobile', 'last_ip', 'last_time'];
        }
        foreach ($hide_field as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }
        return $data;
    }
}
