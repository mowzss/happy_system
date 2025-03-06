<?php

namespace app\common\job;


use app\common\util\SendMailUtil;
use think\queue\Job;

class SendCodeEmailJob
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
        // 检查数据完整性
        if (empty($data['email']) || empty($data['code'])) {
            // 不调用 delete 或 release，任务会自动失败
            return;
        }

        try {
            // 初始化邮件工具类
            $mailUtil = new SendMailUtil();

            // 发送验证码邮件
            if ($mailUtil->sendVerificationCode($data['email'], $data['code'], $data['subject'] ?? '注意查收！您申请的验证码')) {
                $job->delete(); // 如果发送成功，删除任务
            } else {
                throw new \Exception('Failed to send verification code email.');
            }
        } catch (\Exception $e) {
            // 如果任务失败，允许重试
            if ($job->attempts() > 3) { // 最大尝试次数为3次
                // 不调用 delete 或 release，任务会自动失败
                return;
            } else {
                $job->release(5); // 延迟5秒重试
            }
        }
    }
}
