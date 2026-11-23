<?php
declare (strict_types=1);


namespace app\admin\index;

use app\common\controllers\BaseAdmin;
use app\logic\system\DashboardLogic;
use app\model\system\SystemMenu;
use happy\admin\libs\forms\OptionResolver;
use happy\admin\libs\helper\AuthHelper;
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
    //*     * 请求参数：
    //     * - source    数据源配置（JSON 字符串或数组，含 type/table/value/label/where/order）
    //     * - keyword   搜索关键字
    //     * - page      页码，默认 1
    //     * - page_size 每页数量，默认 20，最大 100
    /**
     * 通用字段选项远程数据接口
     *
     * @return void
     */

    // getOptionList 刻意不标记 @auth：退化为仅登录可用节点，
    // 避免每个继承 BaseAdmin 的控制器都生成需逐类授权的 getOptionList 节点。
    // 该注解务必保持删除状态（@auth true 会导致权限节点重复授权）。
    public function getOptionList(): void
    {
        if (!AuthHelper::instance()->isLogin()) {
            $this->error('请先登录');
        }
        $source = $this->request->param('source', []);
        if (is_string($source)) {
            $decoded = json_decode($source, true);
            $source = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($source)) {
            $source = [];
        }
        $keyword = (string)$this->request->param('keyword', '');
        $page = max(1, (int)$this->request->param('page', 1));
        $pageSize = min(100, max(1, (int)$this->request->param('page_size', 20)));
        $autoAdd = (bool)$this->request->param('add', false);
        $this->success(OptionResolver::searchTableSource($source, $keyword, $page, $pageSize, $autoAdd));
    }
}
