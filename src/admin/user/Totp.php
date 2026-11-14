<?php
declare (strict_types=1);


namespace app\admin\user;

use app\model\user\UserInfo;
use app\common\controllers\BaseAdmin;
use happy\admin\libs\helper\QrcodeHelper;
use happy\admin\libs\helper\TotpHelper;

/**
 * 二次验证（TOTP）绑定与解绑
 *
 * 兼容 Microsoft Authenticator / Google Authenticator 等标准 TOTP 应用：
 * 扫码或手动输入 Base32 密钥后，输入一次动态验证码确认即可启用。
 */
class Totp extends BaseAdmin
{
    /**
     * 绑定页面
     * @auth true
     * @return string
     */
    public function index(): string
    {
        $user = $this->currentUser();
        $bound = !empty($user['totp_enabled']) && !empty($user['totp_secret']);

        $secret = '';
        $uri    = '';
        $qrcode = '';
        if (!$bound) {
            // 未绑定：生成一次性临时密钥，10 分钟内有效，避免每次刷新页面都换密钥
            $cacheKey = $this->tempSecretKey((int)$user['id']);
            $secret   = (string)($this->app->cache->get($cacheKey) ?: '');
            if ($secret === '') {
                $secret = TotpHelper::generateSecret();
                $this->app->cache->set($cacheKey, $secret, 600);
            }
            $issuer = (string)(sys_config('site_name') ?: app()->config->get('app.app_name', 'Admin'));
            $uri    = TotpHelper::provisioningUri($issuer, (string)$user['username'], $secret);
            try {
                $qrcode = QrcodeHelper::getQrcode($uri)->getDataUri();
            } catch (\Throwable $e) {
                $qrcode = '';
            }
        }

        $this->assign('bound', $bound);
        $this->assign('secret', $secret);
        $this->assign('uri', $uri);
        $this->assign('qrcode', $qrcode);
        $this->assign('bound_at', !empty($user['totp_bound_at']) ? date('Y-m-d H:i:s', (int)$user['totp_bound_at']) : '');
        $this->assign('username', (string)$user['username']);

        return $this->fetch();
    }

    /**
     * 启用二次验证（需提交一次有效动态码）
     * @auth true
     * @return void
     */
    public function enable(): void
    {
        $user  = $this->currentUser();
        $code  = preg_replace('/\s+/', '', (string)$this->request->post('code', '')) ?? '';
        $cacheKey = $this->tempSecretKey((int)$user['id']);
        $secret   = (string)($this->app->cache->get($cacheKey) ?: '');

        if ($secret === '') {
            $this->error('绑定信息已过期，请刷新页面重新获取二维码');
        }
        if (!TotpHelper::verify($secret, $code, 1, 6, (int)app()->config->get('happy.totp_period', 30))) {
            $this->error('动态验证码不正确，请确认手机时间是否准确后重试');
        }

        $info = UserInfo::find((int)$user['id']);
        if (!$info) {
            $this->error('用户不存在');
        }
        $info->totp_secret   = $secret;
        $info->totp_enabled  = 1;
        $info->totp_bound_at = time();
        if (!$info->save()) {
            $this->error('保存失败，请稍后重试');
        }
        $this->app->cache->delete($cacheKey);

        $this->success('二次验证已启用，下次登录需输入动态验证码');
    }

    /**
     * 关闭二次验证（需提交当前有效动态码）
     * @auth true
     * @return void
     */
    public function disable(): void
    {
        $user = $this->currentUser();
        if (empty($user['totp_enabled']) || empty($user['totp_secret'])) {
            $this->error('当前未启用二次验证');
        }

        $code = preg_replace('/\s+/', '', (string)$this->request->post('code', '')) ?? '';
        if (!TotpHelper::verify((string)$user['totp_secret'], $code, 1, 6, (int)app()->config->get('happy.totp_period', 30))) {
            $this->error('动态验证码不正确，无法关闭');
        }

        $info = UserInfo::find((int)$user['id']);
        if (!$info) {
            $this->error('用户不存在');
        }
        $info->totp_secret   = null;
        $info->totp_enabled  = 0;
        $info->totp_bound_at = null;
        if (!$info->save()) {
            $this->error('保存失败，请稍后重试');
        }

        $this->success('二次验证已关闭');
    }

    /**
     * 取当前登录用户（登录时写入 session 的数组）
     *
     * @return array
     */
    protected function currentUser(): array
    {
        $user = session('user');
        if (empty($user['id'])) {
            $this->error('登录已失效，请重新登录');
            exit;
        }
        // 以数据库为准，避免 session 里的字段过期
        $info = UserInfo::find((int)$user['id']);
        if (!$info) {
            $this->error('用户不存在');
            exit;
        }

        return $info->toArray();
    }

    /**
     * 临时密钥缓存键
     *
     * @param int $userId
     * @return string
     */
    protected function tempSecretKey(int $userId): string
    {
        return 'totp_bind_' . $userId;
    }
}