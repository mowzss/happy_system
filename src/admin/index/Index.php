<?php
declare (strict_types=1);


namespace app\admin\index;

use app\common\controllers\BaseAdmin;
use app\logic\system\DashboardLogic;
use app\model\system\SystemMenu;
use happy\admin\libs\Run;
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
     * 后台欢迎页（仪表盘）
     * 数据组装已下沉至 DashboardLogic
     * @auth true
     * @return string
     */
    public function main(): string
    {
        $data = DashboardLogic::instance()->build();
        foreach ($data as $key => $value) {
            $this->assign($key, $value);
        }

        return $this->fetch();
    }

    /**
     * 切换运行模式
     * @auth true
     * @return void
     */
    public function runMode(): void
    {
        $status = $this->request->post('status/d', 0);
        if ($status === 0) {
            Run::setRun();
            Console::call('optimize:route');
            Console::call('optimize:schema');
            $this->success('已切换线上模式');
        } else {
            Console::call('clear');
            Run::setRun(true);
            $this->success('已切换为调试模式');
        }
    }

    /**
     * 管理菜单数据
     * @login true
     * @return Json|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function menu(): ?Json
    {
        if ($this->request->isAjax()) {
            $menu = SystemMenu::getMenuTree();
            $this->success('获取成功', $menu);
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
        $this->app->cache->clear();
        Console::call('optimize:route');
        Console::call('optimize:schema');
        $path = $this->app->config->get('view.cache_path') ?: $this->app->getRuntimePath() . 'temp';
        Console::call('clear', ['--path=' . $path]);
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        Run::setRun();
        $this->success('清理成功');
    }

    /**
     * 清理模板缓存（runtime/temp 下的编译模板）
     * @auth true
     * @return void
     */
    public function cleanTemp(): void
    {
        $path = $this->app->config->get('view.cache_path') ?: $this->app->getRuntimePath() . 'temp';
        Console::call('clear', ['--path=' . $path]);
        // 清理 opcode 缓存，确保新模板立即生效
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        $this->success('模板缓存清理成功');
    }


    /**
     * 安全退出
     * @login true
     * @return void
     */
    public function logout(): void
    {
        $this->app->session->delete('user');
        $this->success('您已安全退出', hurl('index/index/index'));
    }
}
