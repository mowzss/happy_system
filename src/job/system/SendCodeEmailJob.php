<?php

namespace app\job\system;


use app\common\util\SendMailUtil;
use think\queue\Job;

class SendCodeEmailJob
{
    /**
     * 执行队列任务.
     *
     * @param Job $job 队列任务实例
     * @param array $data 传递的数据
     * @return void|null
     * @throws \Exception
     */
    public function fire(Job $job, array $data)
    {
        // 检查数据完整性
        if (empty($data['email']) || empty($data['code'])) {
            // 不调用 delete 或 release，任务会自动失败
            $job->delete();
            $job->failed(throw new \Exception('Invalid data passed to SendCodeEmailJob.'));
        }

        try {
            // 初始化邮件工具类
            $mailUtil = new SendMailUtil();

            // 发送验证码邮件
            if ($mailUtil->sendVerificationCode($data['email'], $data['code'], $data['subject'] ?? '注意查收！您申请的验证码')) {
                $job->delete(); // 如果发送成功，删除任务
                return;
            } else {
                $job->failed(throw new \Exception('Failed to send verification code email.'));
            }
        } catch (\Exception $e) {
            $job->failed($e);
            // 如果任务失败，允许重试
            if ($job->attempts() > 3) { // 最大尝试次数为3次
                // 不调用 delete 或 release，任务会自动失败
                $job->delete();
                return;
            } else {
                $job->release(5); // 延迟5秒重试
            }

        }
    }
}
