<?php
declare (strict_types=1);

namespace app\admin\index;

use app\common\controllers\BaseAdmin;
use app\model\system\SystemMenu;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Console;
use think\response\Json;

/**
 * 管理后台首页
 */
class Index extends BaseAdmin
{
    /**
     * 后台首页
     * @auth true
     * @return string
     */
    public function index(): string
    {

        return $this->fetch();
    }

    /**
     * 后台欢迎页
     * @auth true
     * @return string
     */
    public function main(): string
    {
        return $this->fetch();
    }

    /**
     * 管理菜单数据
     * @login true
     * @return Json|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function menu()
    {
        if ($this->request->isAjax()) {
            return json(SystemMenu::getMenuTree());
        }
        $this->error('访问方式错误');
    }

    /**
     * 清理缓存
     * @auth true
     * @return void
     */
    public function clean(): void
    {
        Console::call('clear');
        $this->success('清理成功');
    }

    /**
     * 安全退出
     * @login true
     * @return void
     */
    public function logout(): void
    {
        $this->app->session->delete('user');
        $this->success('您已安全退出', hurl('index/index'));
    }
}
