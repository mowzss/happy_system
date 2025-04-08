<?php
declare(strict_types=1);

namespace app\logic\user;

use app\model\user\UserInfo;
use mowzs\lib\BaseLogic;

class UserLogic extends BaseLogic
{
    public function getUserInfoById($uid = ''): array
    {
        if (empty($uid)) {
            return [];
        }
        return UserInfo::with(['usergroup'])->findOrEmpty($uid)->toArray();
    }
}
