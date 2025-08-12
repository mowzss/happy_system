<?php

namespace app\job\system;

use mowzs\lib\extend\push\BaiduPush;
use think\facade\Log;
use think\queue\Job;

class BaiduPushJob
{
    /**
     * 执行任务
     *
     * @param Job $job 当前任务对象
     * @param array $data 数据格式：['urls' => [...]]
     */
    public function fire(Job $job, array $data): void
    {
        $urls = $data['urls'] ?? [];

        if (empty($urls)) {
            $job->delete(); // 无需重试
            return;
        }

        try {
            $pusher = new BaiduPush();
            $result = $pusher->pushBatch($urls);

            if ($result['success']) {
                // 推送成功，删除任务
                $job->delete();
                Log::channel('push')->info('百度推送成功: ' . $result['msg'] . ' URLs: ' . implode(', ', $urls));
            } else {
                // 推送失败，根据失败原因决定是否重试
                $attempts = $job->attempts();
                $maxAttempts = 3;

                if ($attempts < $maxAttempts) {
                    // 可选择延迟重试，比如指数退避
                    $delaySeconds = pow(2, $attempts); // 2, 4, 8 秒
                    $job->release($delaySeconds);
                    Log::channel('push')->error("百度推送第 {$attempts} 次失败，准备重试: " . $result['msg']);
                } else {
                    // 超过最大重试次数，记录日志并删除任务
                    $job->delete();
                    Log::channel('push')->error('百度推送最终失败，已达到最大重试次数: ' . json_encode($result));
                }
            }
        } catch (\Exception $e) {
            $attempts = $job->attempts();
            if ($attempts < 3) {
                $job->release(10); // 延迟10秒重试
                Log::channel('push')->error("百度推送异常（第 {$attempts} 次）: " . $e->getMessage());
            } else {
                $job->delete();
                Log::channel('push')->error('百度推送异常最终失败: ' . $e->getMessage());
            }
        }
    }

    /**
     * 任务失败处理
     *
     * @param array $data
     * @param \Exception $exception
     */
    public function failed(array $data, \Exception $exception): void
    {
        Log::channel('push')->error('百度推送任务最终失败: ' . $exception->getMessage(), $data);
    }

}
