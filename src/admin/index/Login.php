<?php
declare(strict_types=1);


namespace app\admin\index;

use app\model\user\UserInfo;
use think\facade\Db;
use app\common\controllers\BaseAdmin;
use happy\admin\libs\helper\AuthHelper;

/**
 * 登录入口
 */
class Login extends BaseAdmin
{
    /**
     * 登录页面
     * @return string
     */
    public function index(): string
    {
        $admin_entrance = '/' . app()->config->get('happy.admin_entrance', 'admin.php');
        if (AuthHelper::instance()->isLogin()) {
            $this->redirect($admin_entrance);
        }
        if (request()->isPost()) {
            $data = $this->request->post();
            try {
                $this->validate($data, [
                    'username' => 'require',
                    'password' => 'require',
                ], [
                    'username.require' => '用户名不能为空',
                    'password.require' => '密码不能为空',
                ]);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
$username = trim((string)$data['username']);
            $ip       = $this->request->ip();

            // 失败限流：同一「用户名 + IP」在窗口内失败达到阈值即暂时锁定
            $limit   = (int)app()->config->get('happy.login_fail_limit', 5);
            $window  = (int)app()->config->get('happy.login_fail_window', 600);
            $failKey = 'login_fail_' . md5($username . '|' . $ip);
            $fails   = 0;
            if ($limit > 0) {
                $fails = (int)($this->safeCache(fn() => $this->app->cache->get($failKey, 0)) ?? 0);
                if ($fails >= $limit) {
                    $this->writeLoginLog(0, $ip);
                    $this->error('登录失败次数过多，请稍后再试');
                }
            }

            $user = UserInfo::where('username', $username)->findOrEmpty();
            if ($user->isEmpty()) {
                $this->markLoginFail($failKey, $fails, $window, $ip);
                $this->error('账号或密码错误');
            }
            if (!password_verify($data['password'], $user->password)) {
                $this->markLoginFail($failKey, $fails, $window, $ip);
                $this->error('账号或密码错误!');
            }

            // 登录成功：清空失败计数并记录审计日志
            if ($limit > 0) {
                $this->safeCache(function () use ($failKey) {
                    $this->app->cache->delete($failKey);
                    return true;
                });
            }
            $this->writeLoginLog((int)$user['id'], $ip);

            $this->app->session->set('user', $user->toArray());
            
            $save_data = [
                'id' => $user['id'],
                'last_time' => time(),
                'last_ip' => $this->request->ip(),
            ];
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                // 如果是这样，则创建新散列，替换旧散列
                $save_data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            $user->inc('login_num')->save($save_data);
            $this->success('登陆成功', [], $admin_entrance);
        }
return $this->fetch();
    }

    /**
     * 记录一次登录失败：累加计数并写审计日志
     *
     * @param string $key    限流缓存键
     * @param int    $fails  当前已失败次数
     * @param int    $window 计数窗口（秒）
     * @param string $ip     客户端 IP
     */
    protected function markLoginFail(string $key, int $fails, int $window, string $ip): void
    {
        if ($window > 0) {
            $this->safeCache(function () use ($key, $fails, $window) {
                $this->app->cache->set($key, $fails + 1, $window);
                return true;
            });
        }
        $this->writeLoginLog(0, $ip);
    }

    /**
     * 写登录审计日志（成功与失败都记录，失败时 uid 记 0）
     * 日志写入失败不影响登录主流程
     *
     * @param int    $uid 用户 ID，0 表示未匹配到用户
     * @param string $ip  客户端 IP
     */
    protected function writeLoginLog(int $uid, string $ip): void
    {
        try {
            Db::name('user_login_log')->insert([
                'uid'         => $uid,
                'ip'          => $ip,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // 审计日志失败不能阻断登录，静默忽略
        }
    }

    /**
     * 缓存操作降级包装：Redis 等缓存不可用时返回 null，不阻断登录
     *
     * @param \Closure $callback
     * @return mixed
     */
    protected function safeCache(\Closure $callback)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            return null;
        }
    }

}
