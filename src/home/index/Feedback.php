<?php
declare(strict_types=1);


namespace app\home\index;

use app\common\controllers\BaseHome;
use app\model\system\SystemFeedback;
use happy\admin\libs\helper\UserHelper;
use think\captcha\facade\Captcha;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;

class Feedback extends BaseHome
{
    /**
     * 反馈表单：由详情页 [data-feedback] 异步取回后在 layer 弹层中展示
     * @return string
     */
    public function index(): string
    {
        if ($this->request->isPost()) {
            $this->submit();
        }
        $this->assign([
            'categories' => $this->categories(),
            'page_url' => $this->reportedUrl(),
            'captcha_url' => urls('index/feedback/captcha'),
        ]);
        return $this->fetch();
    }

    /**
     * 图形验证码
     * think-captcha 依赖 session，站点已在 happy-libs Service::boot 中注册 SessionInit 中间件
     * @return \think\Response
     */
    public function captcha()
    {
        return Captcha::create();
    }

    /**
     * 提交反馈
     * @return void
     */
    protected function submit(): void
    {
        $data = $this->request->post();

        try {
            $this->validate($data, [
                'category' => 'require|integer',
                'title' => 'require|length:2,100',
                'content' => 'require|length:5,1000',
                'contact_info' => 'max:255',
                'page_url' => 'max:500',
                'captcha' => 'require|captcha',
            ], [
                'category.require' => '请选择反馈类型',
                'title.require' => '请填写标题',
                'title.length' => '标题需在 2-100 个字符之间',
                'content.require' => '请填写反馈内容',
                'content.length' => '内容需在 5-1000 个字符之间',
                'captcha.require' => '请填写验证码',
                'captcha.captcha' => '验证码错误',
            ]);
        } catch (ValidateException $e) {
            $this->error($e->getMessage());
        }

        $category = (int)$data['category'];
        $categories = $this->categories();
        if (!isset($categories[$category])) {
            $this->error('请选择正确的反馈类型');
        }

        $page_url = $this->normalizePageUrl((string)($data['page_url'] ?? ''));
        if ($page_url === '') {
            $this->error('反馈页面地址不正确，请重新提交');
        }

        $contact_info = trim((string)($data['contact_info'] ?? ''));
        $uid = (int)UserHelper::instance()->getUserId(0);
        $time = time();

        $save_data = [
            'uid' => $uid > 0 ? $uid : null,
            'contact_info' => $contact_info !== '' ? $contact_info : null,
            'category' => $category,
            'title' => trim((string)$data['title']),
            'page_url' => $page_url,
            'content' => trim((string)$data['content']),
            'status' => 0,
        ];

        (new SystemFeedback())->save($save_data);

        $this->notify($save_data, $categories[$category], $time);

        // url 传空串：默认会取 referer，异步提交成功后跳回本页会冲掉提示
        $this->success('提交成功，感谢您的反馈', '', '');
    }

    /**
     * 反馈类别：类别名在项目的 config/extra/feedback.php 中配置
     * @return array
     */
    protected function categories(): array
    {
        $this->app->config->load('extra/feedback', 'feedback');
        return $this->app->config->get('feedback.category', []) ?: [1 => '举报', 2 => '反馈', 3 => '建议'];
    }

    /**
     * 反馈弹层中默认带出的反馈页面地址
     * @return string
     */
    protected function reportedUrl(): string
    {
        $url = $this->normalizePageUrl((string)$this->request->param('url', ''));
        return $url !== '' ? $url : $this->request->domain() . '/';
    }

    /**
     * 只接受本站域名下的 http(s) 链接，防止外部链接与伪协议进入后台列表
     * @param string $url
     * @return string 不合法时返回空串
     */
    protected function normalizePageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if ($url[0] === '/' && !str_starts_with($url, '//')) {
            $url = $this->request->domain() . $url;
        }
        if (!preg_match('#^https?://#i', $url)) {
            return '';
        }
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        if ($host === '' || !$this->isLocalHost($host)) {
            return '';
        }
        return mb_substr($url, 0, 500);
    }

    /**
     * 判断是否本站域名：当前请求域名、后台配置的 PC/WAP 域名及其子域名
     * @param string $host
     * @return bool
     * @throws \Throwable
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function isLocalHost(string $host): bool
    {
        $hosts = [(string)$this->request->host(true)];
        foreach (['site_domain', 'site_wap_domain'] as $name) {
            $domain = trim((string)sys_config($name, ''));
            if ($domain !== '') {
                $hosts[] = (string)parse_url($domain, PHP_URL_HOST);
            }
        }
        foreach (array_filter($hosts) as $local) {
            $local = strtolower((string)$local);
            if ($host === $local || str_ends_with($host, '.' . $local)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 新反馈入库后通知「通知管理 - 反馈通知目标邮箱」，未配置则静默跳过
     * @param array $data
     * @param string $category_name
     * @param int $time
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \Throwable
     */
    protected function notify(array $data, string $category_name, int $time): void
    {
        $emails = $this->notifyEmails();
        if (empty($emails)) {
            return;
        }

        $site_name = (string)sys_config('site_name', '');
        $subject = '收到新的' . $category_name . ($site_name !== '' ? ' - ' . $site_name : '');
        $body = $this->notifyBody($data, $category_name, $time);

        // 逐个收件人发送，避免收件人地址在邮件头里互相可见
        foreach ($emails as $email) {
            try {
                send_email($email, $subject, $body, true);
            } catch (\Throwable $e) {
                $this->app->log->error('反馈通知入队失败[' . $email . ']: ' . $e->getMessage());
            }
        }
    }

    /**
     * 读取并校验通知目标邮箱，多个用逗号（中英文）或空格分隔
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \Throwable
     */
    protected function notifyEmails(): array
    {
        $config = (string)sys_config('feedback_notify_emails', '');
        if (trim($config) === '') {
            return [];
        }
        $emails = [];
        foreach (preg_split('/[,，\s]+/u', $config) as $email) {
            $email = trim($email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }
        return array_values(array_unique($emails));
    }

    /**
     * 通知邮件正文
     * @param array $data
     * @param string $category_name
     * @param int $time
     * @return string
     */
    protected function notifyBody(array $data, string $category_name, int $time): string
    {
        $esc = fn(string $value) => htmlspecialchars($value, ENT_QUOTES);
        $page_url = $esc((string)$data['page_url']);

        $rows = [
            '类别' => $esc($category_name),
            '标题' => $esc((string)$data['title']),
            '反馈页面' => '<a href="' . $page_url . '">' . $page_url . '</a>',
            '联系方式' => $data['contact_info'] ? $esc((string)$data['contact_info']) : '未填写',
            '提交人' => $data['uid'] ? '用户ID ' . (int)$data['uid'] : '游客',
            '提交时间' => date('Y-m-d H:i:s', $time),
            '内容' => nl2br($esc((string)$data['content'])),
        ];

        $html = '';
        foreach ($rows as $label => $value) {
            $html .= '<tr><td style="padding:6px 12px;border:1px solid #e5e5e5;background:#fafafa;white-space:nowrap;">'
                . $label . '</td><td style="padding:6px 12px;border:1px solid #e5e5e5;">' . $value . '</td></tr>';
        }

        return '<p>后台「系统 - 举报反馈」中可查看与处理。</p><table style="border-collapse:collapse;font-size:14px;">' . $html . '</table>';
    }
}
