<?php
declare(strict_types=1);

namespace app\home\install;

use app\common\util\SqlExecutor;
use mowzs\lib\Controller;
use PDO;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;

class Index extends Controller
{

    /**
     * @return string
     */
    public function index(): string
    {
        if ($this->isInstalled()) {
            $this->error('您已安装过系统!');
        }
        // 显示安装表单
        return $this->fetch();
    }

    public function install(): \think\response\Json
    {
        // 检查是否已经安装
        if ($this->isInstalled()) {
            return json(['status' => 'error', 'msg' => '系统已安装，请勿重复安装！']);
        }

        // 获取并验证表单数据
        $data = Request::post();
        if (!$this->vali($data)) {
            return json(['status' => 'error', 'msg' => '表单验证失败']);
        }

        // 检查运行环境
        if (!$this->checkEnvironment()) {
            return json(['status' => 'error', 'msg' => '运行环境不符合要求']);
        }

        // 检测数据库连接
        if (!$this->testDbConnection($data)) {
            return json(['status' => 'error', 'msg' => '数据库连接失败']);
        }

        // 写入配置文件
        if (!$this->writeConfigFile($data)) {
            return json(['status' => 'error', 'msg' => '写入配置文件失败']);
        }

        // 执行SQL文件
        $sqlExecutor = new SqlExecutor();
        try {
            $sqlExecutor->execute('system/install.sql');
        } catch (\Exception $e) {
            return json(['status' => 'error', 'msg' => '执行SQL文件失败: ' . $e->getMessage()]);
        }

        // 创建管理员账户
        if (!$this->createAdmin($data)) {
            return json(['status' => 'error', 'msg' => '创建管理员账户失败']);
        }
        if (!$this->writeHappyConfigFile($data)) {
            return json(['status' => 'error', 'msg' => 'Happy.php配置文件设置失败！']);
        }
        return json(['status' => 'success', 'msg' => '安装成功']);
    }

    /**
     * @return bool
     */
    protected function isInstalled(): bool
    {
        return Config::get('happy.installed', false) === true;
    }

    protected function vali($data)
    {
        // 实现表单验证逻辑
        // 这里可以使用thinkPHP内置的验证器
        // 简单示例：确保所有必填字段存在
        foreach (['db_host', 'db_name', 'db_user', 'db_pass', 'db_prefix'] as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    public function checkEnvironment(): \think\response\Json
    {
        $requirements = [
            'php版本: >=8.0' => version_compare(PHP_VERSION, '8.0', '>='),
            'curl扩展' => extension_loaded('curl'),
            'fileinfo扩展' => extension_loaded('fileinfo'),
            'openssl扩展' => extension_loaded('openssl'),
            'pdo扩展' => extension_loaded('pdo'),
        ];

        foreach ($requirements as &$requirement) {
            $requirement = $requirement ? '支持' : '不支持';
        }


        return json(['status' => 'success', 'requirements' => $requirements]);
    }

    public function checkDbConnection()
    {
        // 获取并验证表单数据
        $data = Request::post();
        if (!$this->vali($data)) {
            return json(['status' => 'error', 'msg' => '表单验证失败']);
        }

        // 检测数据库连接
        if ($this->testDbConnection($data)) {
            return json(['status' => 'success', 'msg' => '数据库连接成功']);
        } else {
            return json(['status' => 'error', 'msg' => '数据库连接失败']);
        }
    }


    protected function testDbConnection($data)
    {
        try {
            $dsn = "mysql:host={$data['db_host']};dbname={$data['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // 获取MySQL版本
            $stmt = $pdo->query("SELECT VERSION() AS mysql_version");
            $mysqlVersion = $stmt->fetch(PDO::FETCH_ASSOC)['mysql_version'];

            // 检查MySQL版本是否为5.7或更高
            if (version_compare($mysqlVersion, '5.7', '<')) {
                throw new \Exception("MySQL版本太低，需要5.7或更高版本。当前版本：{$mysqlVersion}");
            }

            // 测试连接
            $pdo->query('SELECT 1');
            return true;
        } catch (\PDOException|\Exception $e) {
            // 记录错误信息或直接返回
            // 这里简单处理，您可以根据实际情况调整
            error_log($e->getMessage());
            return false;
        }
    }

    protected function writeConfigFile($data): bool
    {
        // 定义默认配置模板路径
        $templatePath = app()->getBasePath() . 'common/install/system/database.tpl';
        $configPath = app()->getConfigPath() . 'database.php';

        // 检查模板文件是否存在
        if (!file_exists($templatePath)) {
            return false;
        }

        // 读取模板内容
        $configContent = file_get_contents($templatePath);

        // 替换配置项
        $replacements = [
            '{DB_HOST}' => $data['db_host'],
            '{DB_NAME}' => $data['db_name'],
            '{DB_USER}' => $data['db_user'],
            '{DB_PASS}' => $data['db_pass'],
            '{DB_PREFIX}' => $data['db_prefix'],
        ];

        foreach ($replacements as $placeholder => $value) {
            $configContent = str_replace($placeholder, $value, $configContent);
        }

        // 写入配置文件
        return file_put_contents($configPath, $configContent) !== false;
    }

    protected function writeHappyConfigFile($data): bool
    {
        // 定义默认配置模板路径
        $templatePath = app()->getBasePath() . 'common/install/system/happy.tpl';
        $configPath = app()->getConfigPath() . 'happy.php';

        // 检查模板文件是否存在
        if (!file_exists($templatePath)) {
            return false;
        }
        // 读取模板内容
        $configContent = file_get_contents($templatePath);
        // 替换配置项
        $replacements = [
            '{USERNAME}' => $data['admin_username'],
        ];

        foreach ($replacements as $placeholder => $value) {
            $configContent = str_replace($placeholder, $value, $configContent);
        }
        // 写入配置文件
        return file_put_contents($configPath, $configContent) !== false;
    }

    protected function createAdmin($data)
    {

        $hashedPassword = password_hash(md5($data['admin_password']), PASSWORD_BCRYPT);

        return Db::name('UserInfo')->insert([
            'username' => $data['admin_username'],
            'nickname' => '管理员',
            'password' => $hashedPassword,
            'email' => $data['admin_email'],
        ]);
    }


}
