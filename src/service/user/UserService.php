<?php
declare(strict_types=1);

namespace app\service\user;

use app\model\user\UserInfo;
use app\service\BaseService;

class UserService extends BaseService
{
    /**
     * 用户信息模型
     * @var UserInfo
     */
    protected UserInfo $userInfoModel;

    /**
     * @return void
     */
    protected function initialize(): void
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->userInfoModel = new  UserInfo();
    }

    public function getUserInfoById($uid = ''): array
    {
        if (empty($uid)) {
            return [];
        }
        return $this->userInfoModel->with(['usergroup'])->findOrEmpty($uid)->toArray();
    }
}
