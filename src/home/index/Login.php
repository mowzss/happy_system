<?php
declare(strict_types=1);


namespace app\home\index;

use think\oauth\OAuth;
use app\model\user\UserInfo;
use app\model\user\UserOauth;
use think\db\exception\DbException;
use app\common\controllers\BaseHome;
use happy\admin\libs\helper\CodeHelper;
use happy\admin\libs\helper\EventHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

class Login extends BaseHome
{
    /**
     * 第三方登录平台注册表
     *
     * 新增一个平台的步骤：
     * 1. 在 stc/extend/think/oauth/driver 下实现一个 DriverInterface 驱动
     * 2. 在 think\oauth\OAuth::$drivers 中登记驱动别名
     * 3. 在此数组追加一条配置，keys 的值填后台「系统配置」中的配置项名称
     *
     * 字段说明：
     * name       平台显示名
     * driver     OAuth 扩展包中的驱动别名
     * web        是否支持网页授权跳转（小程序这类前端拿 code 的填 false）
     * state      授权回跳是否校验 state（防 CSRF）
     * authorize  平台授权页地址
     * client_key 授权页中 appid 对应的参数名（QQ 是 client_id，微信是 appid）
     * scope      授权作用域
     * keys       驱动配置项 => 系统配置名称
     *
     * @var array<string, array>
     */
    protected array $oauthPlatforms = [
        'qq' => [
            'name' => 'QQ',
            'driver' => 'qq_web',
            'web' => true,
            'state' => true,
            'authorize' => 'https://graph.qq.com/oauth2.0/authorize',
            'client_key' => 'client_id',
            'scope' => 'get_user_info',
            'keys' => [
                'appid' => 'oauth_qq_appid',
                'appkey' => 'oauth_qq_appkey',
            ],
        ],
        'wxxcx' => [
            'name' => '微信',
            'driver' => 'wechat_mini',
            'web' => false,
            'state' => false,
            'keys' => [
                'appid' => 'oauth_wxxcx_appid',
                'secret' => 'oauth_wxxcx_appsecret',
            ],
        ],
    ];
    
    /**
     * 登录
     * @return string
     */
    public function index(): string
    {
        if ($this->request->isPost()) {
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
                $this->error($e->getMessage(), '', $this->loginUrl());
            }
            $user = UserInfo::where('username', $data['username'])->findOrEmpty();
            if ($user->isEmpty()) {
                $this->error('账号或密码错误', '', $this->loginUrl());
            }
            if (!password_verify($data['password'], $user->password)) {
                $this->error('账号或密码错误!', '', $this->loginUrl());
            }
            if ((int)$user->status !== 1) {
                $this->error('账号已被禁用，请联系管理员', '', $this->loginUrl());
            }
            $this->loginSuccess($user, '登陆成功', (string)$data['password']);
        }
        return $this->fetch('', ['oauth_platforms' => $this->webPlatforms()]);
    }
    
    /**
     * 登录页地址
     * @return string
     */
    private function loginUrl(): string
    {
        return urls('index/login/index');
    }
    
    /**
     * 登录成功后的统一处理：更新登录信息、写会话、触发事件、跳转
     * @param UserInfo $user
     * @param string $msg
     * @param string|null $plain_password 明文密码，仅账号密码登录时传入，用于散列升级
     * @return void
     */
    private function loginSuccess(UserInfo $user, string $msg = '登陆成功', ?string $plain_password = null): void
    {
        $save_data = [
            'last_time' => time(),
            'last_ip' => $this->request->ip(),
        ];
        if ($plain_password !== null && password_needs_rehash((string)$user->password, PASSWORD_DEFAULT)) {
            // 如果是这样，则创建新散列，替换旧散列
            $save_data['password'] = password_hash($plain_password, PASSWORD_DEFAULT);
        }
        $user->inc('login_num')->save($save_data);
        
        $user_data = $user->hidden(['password'])->toArray();
        $this->app->session->set('user', $user_data);
        EventHelper::instance()->listen('UserLogin', $user_data);
        
        $this->success($msg, [], urls('index/index'));
    }
    
    /**
     * 供模板渲染的、已开启网页授权的平台列表
     * @return array
     */
    private function webPlatforms(): array
    {
        $list = [];
        foreach ($this->oauthPlatforms as $type => $platform) {
            if (empty($platform['web'])) {
                continue;
            }
            // 未配置 appid 的平台不在登录页展示
            $appid_name = $platform['keys']['appid'] ?? '';
            if ($appid_name === '' || trim((string)sys_config($appid_name, '')) === '') {
                continue;
            }
            $list[] = [
                'type' => $type,
                'name' => $platform['name'],
                'url' => $this->oauthUrl($type),
            ];
        }
        return $list;
    }
    
    /**
     * 第三方登录统一入口（支持多平台）
     *
     * 不带 code 时：跳转到对应平台的授权页
     * 带 code 时：换取用户信息 -> 已绑定则登录，未绑定则自动注册并登录
     *
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \Throwable
     */
    public function oauth(): void
    {
        $type = strtolower(trim((string)$this->request->param('type', '')));
        if (!isset($this->oauthPlatforms[$type])) {
            $this->error('不支持的登录方式', '', $this->loginUrl());
        }
        $platform = $this->oauthPlatforms[$type];
        
        // 用户在平台授权页点了取消
        if ($this->request->param('error')) {
            $this->error('已取消授权登录', '', $this->loginUrl());
        }
        
        $code = trim((string)$this->request->param('code', ''));
        
        // 第一步：没有授权码，跳转到平台授权页
        if ($code === '') {
            if (empty($platform['web'])) {
                $this->error($platform['name'] . '登录不支持网页授权', '', $this->loginUrl());
            }
            $config = $this->platformConfig($type);
            $this->redirect($this->authorizeUrl($type, $platform, $config));
            return;
        }
        
        // 第二步：校验 state，防止 CSRF
        if (!empty($platform['state'])) {
            $this->checkState($type);
        }
        
        // 第三步：授权码换取第三方用户信息
        try {
            $oauth_user = (new OAuth($platform['driver'], $this->platformConfig($type)))->getUserInfo($code);
        } catch (\Throwable $e) {
            $this->app->log->error('第三方登录失败[' . $type . ']: ' . $e->getMessage());
            $this->error('第三方登录失败，请稍后重试', '', $this->loginUrl());
        }
        
        if (empty($oauth_user['openid'])) {
            $this->error('未获取到第三方账号信息', '', $this->loginUrl());
        }
        
        // 第四步：绑定登录 / 自动注册
        [$user, $is_new] = $this->oauthToUser($type, $platform, $oauth_user);
        $this->loginSuccess($user, $is_new ? '注册并登录成功' : '登陆成功');
    }
    
    /**
     * 读取平台的驱动配置
     * @param string $type
     * @return array
     * @throws \Throwable
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function platformConfig(string $type): array
    {
        $platform = $this->oauthPlatforms[$type];
        $config = [];
        foreach ($platform['keys'] as $key => $config_name) {
            $config[$key] = trim((string)sys_config($config_name, ''));
        }
        $config['redirect_uri'] = $this->redirectUri($type);
        
        if ($config['appid'] === '') {
            $this->error($platform['name'] . '登录暂未开放', '', $this->loginUrl());
        }
        return $config;
    }
    
    /**
     * 拼装平台授权页地址
     * @param string $type
     * @param array $platform
     * @param array $config
     * @return string
     */
    private function authorizeUrl(string $type, array $platform, array $config): string
    {
        $query = [
            'response_type' => 'code',
            $platform['client_key'] => $config['appid'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => $platform['scope'],
        ];
        
        if (!empty($platform['state'])) {
            $state = CodeHelper::randomString(16);
            $this->app->session->set($this->stateKey($type), $state);
            $query['state'] = $state;
        }
        
        $authorize = (string)$platform['authorize'];
        return $authorize . (str_contains($authorize, '?') ? '&' : '?') . http_build_query($query);
    }
    
    /**
     * 校验授权回跳的 state（一次性使用）
     * @param string $type
     * @return void
     */
    private function checkState(string $type): void
    {
        $key = $this->stateKey($type);
        $session_state = (string)$this->app->session->get($key, '');
        $state = (string)$this->request->param('state', '');
        // 无论校验是否通过都清除，避免重复使用
        $this->app->session->delete($key);
        
        if ($session_state === '' || !hash_equals($session_state, $state)) {
            $this->error('登录状态已失效，请重新登录', '', $this->loginUrl());
        }
    }
    
    /**
     * 把第三方用户信息落到本站账号：已绑定则返回原账号，否则自动注册
     * @param string $type
     * @param array $platform
     * @param array $oauth_user
     * @return array{0: UserInfo, 1: bool} [用户模型, 是否新注册]
     */
    private function oauthToUser(string $type, array $platform, array $oauth_user): array
    {
        $bind = UserOauth::where(['type' => $type, 'openid' => $oauth_user['openid']])->findOrEmpty();
        
        if (!$bind->isEmpty()) {
            $user = (new UserInfo())->findOrEmpty($bind->uid);
            if (!$user->isEmpty()) {
                if ((int)$user->status !== 1) {
                    $this->error('账号已被禁用，请联系管理员', '', $this->loginUrl());
                }
                return [$user, false];
            }
            // 绑定记录还在但用户已被删除，清掉脏绑定后走注册流程
            $bind->delete();
        }
        
        return [$this->oauthRegister($type, $platform, $oauth_user), true];
    }
    
    /**
     * 第三方登录入口地址
     *
     * 项目路由关闭了 url_common_param，直接传 vars 会把参数拼进路径。
     * 这里统一走查询串，保证回调地址是一条固定 URL，便于在开放平台登记。
     *
     * @param string $type
     * @return string
     */
    private function oauthUrl(string $type): string
    {
        $path = urls('index/login/oauth');
        return $path . (str_contains($path, '?') ? '&' : '?') . 'type=' . urlencode($type);
    }
    
    /**
     * 授权回调地址
     *
     * 优先读取后台配置项 oauth_{type}_redirect_uri，未配置时按站点域名自动拼装。
     * 注意：最终地址必须与开放平台后台登记的回调地址完全一致，否则平台会拒绝回跳。
     *
     * @param string $type
     * @return string
     */
    private function redirectUri(string $type): string
    {
        $custom = trim((string)sys_config('oauth_' . $type . '_redirect_uri', ''));
        if ($custom !== '') {
            return $custom;
        }
        
        $domain = rtrim((string)sys_config('site_domain', ''), '/');
        if ($domain === '') {
            $domain = rtrim($this->request->domain(), '/');
        }
        $path = $this->oauthUrl($type);
        
        return $domain . (str_starts_with($path, '/') ? $path : '/' . $path);
    }
    
    /**
     * @param string $type
     * @return string
     */
    private function stateKey(string $type): string
    {
        return 'oauth_state_' . $type;
    }
    
    /**
     * 第三方账号自动注册
     * @param string $type
     * @param array $platform
     * @param array $oauth_user
     * @return UserInfo
     */
    private function oauthRegister(string $type, array $platform, array $oauth_user): UserInfo
    {
        $user = new UserInfo();
        $user->username = $this->buildUsername($type);
        // 第三方注册没有明文密码，写入一个不可逆的随机散列
        $user->password = password_hash(CodeHelper::randomString(16), PASSWORD_DEFAULT);
        $user->nickname = $this->buildNickname($platform, $oauth_user);
        $user->avatar = mb_substr((string)($oauth_user['avatar'] ?? ''), 0, 500);
        $user->sex = $this->buildSex($oauth_user);
        $user->mobile = null;
        $user->last_ip = $this->request->ip();
        $user->last_time = time();
        $user->login_num = 0;
        $user->save();
        
        // user_oauth 表只有 uid/type/openid 三个业务字段，unionid 未建列，不写入
        UserOauth::create([
            'uid' => $user->id,
            'type' => $type,
            'openid' => (string)$oauth_user['openid'],
        ]);
        
        $user_data = $user->hidden(['password'])->toArray();
        EventHelper::instance()->listen('UserRegister', $user_data);
        
        return $user;
    }
    
    /**
     * 生成不重复的用户名
     * @param string $type
     * @return string
     */
    private function buildUsername(string $type): string
    {
        for ($i = 0; $i < 10; $i++) {
            $username = $type . '_' . CodeHelper::randomString(12);
            if (UserInfo::where('username', $username)->count() === 0) {
                return $username;
            }
        }
        // 兜底：加上时间戳，几乎不可能再冲突
        return $type . '_' . time() . CodeHelper::randomString(10);
    }
    
    /**
     * @param array $platform
     * @param array $oauth_user
     * @return string
     */
    private function buildNickname(array $platform, array $oauth_user): string
    {
        $nickname = trim((string)($oauth_user['nickname'] ?? ''));
        if ($nickname !== '') {
            return mb_substr($nickname, 0, 20);
        }
        return $platform['name'] . '用户' . CodeHelper::randomString(10);
    }
    
    /**
     * 性别映射：0 未知 / 1 男 / 2 女
     * @param array $oauth_user
     * @return int
     */
    private function buildSex(array $oauth_user): int
    {
        $gender = (string)($oauth_user['raw']['gender'] ?? '');
        return match ($gender) {
            '男', 'male', '1' => 1,
            '女', 'female', '2' => 2,
            default => 0,
        };
    }
}
