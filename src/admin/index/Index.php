<?php
declare (strict_types=1);

namespace app\admin\index;

use app\common\controllers\BaseAdmin;
use app\model\system\SystemMenu;
use mowzs\lib\helper\ComposerHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Console;
use think\facade\Db;
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
        $system = [
            //系统信息
            'info' => php_uname(),
            //服务器ip
            'ip' => $this->request->server('server_addr', $this->request->server('local_addr', getHostByName(getHostName()))),
            //php版本
            'php_version' => PHP_VERSION,
            //mysql版本
            'mysql_version' => Db::query("select version()")[0]['version()'],
            //运行内存
            'php_run_memory' => round(memory_get_peak_usage() / 1024 / 1024 / 1024, 6) . 'G',
            //磁盘空间
            'disk_total_space' => round(disk_total_space('/') / 1024 / 1024 / 1024, 2) . 'G',
            //剩余空间
            'disk_free_space' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . 'G',
            //运行模式
            'php_sapi' => php_sapi_name(),
            //运行环境
            'php_os' => PHP_OS,
        ];
        // PHP 扩展支持状态
        $php_ext = [
            'pdo' => extension_loaded('pdo') ? 'on' : 'off',
            'curl' => extension_loaded('curl') ? 'on' : 'off',
            'fileinfo' => extension_loaded('fileinfo') ? 'on' : 'off',
            'openssl' => extension_loaded('openssl') ? 'on' : 'off',
            'gd' => extension_loaded('gd') ? 'on' : 'off',
        ];
        try {
            $happyModules = ComposerHelper::getPackagesByType();
            $systemModules = ComposerHelper::getPackagesExceptType();
            $this->assign('system_modules', $systemModules);
            $this->assign('happy_modules', $happyModules);
        } catch (\Exception $e) {

        }
        $this->assign('php_ext', $php_ext);
        $this->assign('system', $system);
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
            $menu = SystemMenu::getMenuTree();
            return json($menu);
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
        $this->app->cache->clear();
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
        $this->success('您已安全退出', hurl('index/index/index'));
    }
}
