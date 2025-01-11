<?php
declare(strict_types=1);

namespace app\common\util;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class SendMailUtil
{
    protected PHPMailer $mail;
    protected array $config = [];
    protected string $fromEmail;
    protected string $fromName;
    protected array $toEmails = [];
    protected string $subject = '';
    protected string $body = '';
    protected bool $isHtml = false;
    protected array $attachments = [];

    public function __construct()
    {
        // 初始化配置
        $this->getConfig();
        // 初始化 PHPMailer
        $this->mail = new PHPMailer(true);
    }

    /**
     * 获取邮件服务器配置.
     *
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function getConfig(): void
    {
        $this->config = [
            'host' => sys_config('mail_host'),
            'port' => sys_config('mail_port'),
            'username' => sys_config('mail_username'),
            'password' => sys_config('mail_password'),
            'smtp_secure' => sys_config('mail_smtp_secure', 'ssl'), // 默认为 ssl
            'charset' => sys_config('mail_charset', 'utf-8'), // 默认为 utf-8
        ];
        $this->fromEmail = sys_config('mail_username');
        $this->fromName = sys_config('mail_from_name', 'No Reply');
    }

    /**
     * 设置发件人信息.
     *
     * @param string $email
     * @param string|null $name
     * @return $this
     */
    public function setFrom(string $email, ?string $name = null): self
    {
        $this->fromEmail = $email;
        $this->fromName = $name ?? $this->fromName;
        return $this;
    }

    /**
     * 添加收件人.
     *
     * @param array|string $emails 单个邮箱地址或多个邮箱地址数组
     * @param string|null $name 收件人名称
     * @return $this
     */
    public function addTo(array|string $emails, ?string $name = null): self
    {
        if (is_string($emails)) {
            $this->toEmails[] = ['email' => $emails, 'name' => $name];
        } elseif (is_array($emails)) {
            foreach ($emails as $email) {
                $this->toEmails[] = is_array($email) ? $email : ['email' => $email, 'name' => null];
            }
        }
        return $this;
    }

    /**
     * 设置邮件主题.
     *
     * @param string $subject
     * @return $this
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * 设置邮件正文.
     *
     * @param string $body
     * @param bool $isHtml
     * @return $this
     */
    public function setBody(string $body, bool $isHtml = false): self
    {
        $this->body = $body;
        $this->isHtml = $isHtml;
        return $this;
    }

    /**
     * 添加附件.
     *
     * @param string $path 文件路径
     * @param string|null $name 附件显示名称
     * @return $this
     */
    public function addAttachment(string $path, ?string $name = null): self
    {
        $this->attachments[] = ['path' => $path, 'name' => $name];
        return $this;
    }

    /**
     * 发送验证码邮件.
     *
     * @param string $email
     * @param string $code
     * @param string $subject
     * @return bool
     * @throws MailException
     */
    public function sendVerificationCode(string $email, string $code, string $subject = '注意查收！您申请的验证码'): bool
    {
        $this->setSubject($subject)
            ->addTo($email)
            ->setBody("您的验证码是: $code", false);

        return $this->send();
    }

    /**
     * 发送邮件.
     *
     * @return bool
     * @throws MailException
     */
    public function send(): bool
    {
        try {
            // 配置服务器
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];
            $this->mail->SMTPSecure = $this->config['smtp_secure'];
            $this->mail->Port = $this->config['port'];

            // 发件人
            $this->mail->setFrom($this->fromEmail, $this->fromName);

            // 收件人
            foreach ($this->toEmails as $recipient) {
                $this->mail->addAddress($recipient['email'], $recipient['name']);
            }

            // 主题
            $this->mail->Subject = $this->subject;

            // 正文
            $this->mail->isHTML($this->isHtml);
            $this->mail->Body = $this->body;

            // 附件
            foreach ($this->attachments as $attachment) {
                $this->mail->addAttachment($attachment['path'], $attachment['name']);
            }

            // 清除所有收件人、附件等
            $this->clear();

            return $this->mail->send();
        } catch (MailException $e) {
            // 处理异常
            throw $e;
        }
    }

    /**
     * 清除当前实例中的所有收件人、附件等.
     */
    protected function clear(): void
    {
        $this->toEmails = [];
        $this->subject = '';
        $this->body = '';
        $this->isHtml = false;
        $this->attachments = [];
    }
}
