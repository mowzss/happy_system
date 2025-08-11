<?php

namespace app\job\system;


use app\common\util\SendMailUtil;
use think\queue\Job;

class SendEmailJob
{

    /**
     * 执行队列任务.
     *
     * @param Job $job 队列任务实例
     * @param array $data 传递的数据
     * @return void
     */
    public function fire(Job $job, array $data): void
    {
        try {
            // 初始化邮件工具类
            $mailUtil = new SendMailUtil();

            // 设置发件人信息（可选）
            $fromEmail = $data['from_email'] ?? null;
            $fromName = $data['from_name'] ?? null;
            if ($fromEmail) {
                $mailUtil->setFrom($fromEmail, $fromName);
            }

            // 添加收件人
            if (is_array($data['to'])) {
                foreach ($data['to'] as $recipient) {
                    $mailUtil->addTo($recipient);
                }
            } else {
                $mailUtil->addTo($data['to']);
            }

            // 设置主题和正文
            $mailUtil->setSubject($data['subject'])
                ->setBody($data['body'], isset($data['is_html']) && $data['is_html']);

            // 添加附件（如果有）
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    $mailUtil->addAttachment($attachment['path'], $attachment['name'] ?? null);
                }
            }

            // 发送邮件
            if ($mailUtil->send()) {
                $job->delete(); // 如果发送成功，删除任务
            } else {
                $job->failed(throw new \Exception('Failed to send email.'));
            }
        } catch (\Exception $e) {
            $job->failed($e);
            // 如果任务失败，允许重试
            if ($job->attempts() > 3) { // 最大尝试次数为3次
                // 不调用 delete 或 release，任务会自动失败
                $job->delete();
            } else {
                $job->release(5); // 延迟5秒重试
            }

        }
    }
}
