<?php

namespace app\home\install;

use app\common\controllers\BaseHome;
use app\common\util\SqlExecutor;
use PDO;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;

class Index extends BaseHome
{
    /**
     * @return string
     */
    public function index(): string
    {
        // 检查是否已经安装
        if ($this->isInstalled()) {
            return '系统已安装，请勿重复安装！';
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
            $sqlExecutor->execute('install.sql');
        } catch (\Exception $e) {
            return json(['status' => 'error', 'msg' => '执行SQL文件失败: ' . $e->getMessage()]);
        }

        // 创建管理员账户
        if (!$this->createAdmin($data)) {
            return json(['status' => 'error', 'msg' => '创建管理员账户失败']);
        }

        return json(['status' => 'success', 'msg' => '安装成功']);
    }

    protected function isInstalled()
    {
        return Config::get('install.installed') === true;
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

    protected function checkEnvironment()
    {
        // 检查PHP版本
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            return false;
        }

        // 检查其他环境要求（例如扩展）
        // 可以根据需要添加更多检查
        return extension_loaded('pdo') && extension_loaded('mysqli');
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
            // 测试连接
            $pdo->query('SELECT 1');
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    protected function writeConfigFile($data): bool
    {
        // 定义默认配置模板路径
        $templatePath = app()->getBasePath() . 'common/install/database.tpl';
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

    protected function createAdmin($data)
    {
        // 假设有一个admin表用于存储管理员信息
        // 注意：这里应该使用安全的方式处理密码，比如哈希
        $hashedPassword = password_hash(md5($data['admin_password']), PASSWORD_BCRYPT);
        return Db::name('UserInfo')->insert([
            'username' => $data['admin_username'],
            'password' => $hashedPassword,
            'email' => $data['admin_email'],
        ]);
    }
}
