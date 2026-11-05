<?php


namespace app\job\system;

use think\queue\Job;
use think\facade\Log;
use happy\admin\libs\extend\push\BaiduPush;
use happy\admin\libs\Exception\LibsException;

class BaiduPushJob
{
    /**
     * 最大重试次数
     */
    protected const MAX_ATTEMPTS = 3;

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
                return;
            }

            // 不可继续推送（配额用完 / token 失效 / 站点未验证 / 接口错误等），当日后续不再重试
            if (empty($result['retryable'])) {
                $job->delete();

                if (!empty($result['skipped'])) {
                    // 已在当日停止推送，属于预期内的跳过，不用 error 级别刷日志
                    Log::channel('push')->info('百度推送已跳过（今日停止推送）: ' . $result['msg']);
                    return;
                }

                Log::channel('push')->error(
                    '百度推送已终止，当日不再推送: ' . $result['msg']
                    . ' | error_code=' . ($result['error_code'] ?? '-')
                    . ' | http_code=' . ($result['http_code'] ?? '-')
                    . ' | desc=' . ($result['desc'] ?? '-')
                    . ' | URLs: ' . implode(', ', $urls)
                );
                return;
            }

            // 可重试的错误（如 500 internal error、网络异常）
            $attempts = $job->attempts();

            if ($attempts < self::MAX_ATTEMPTS) {
                // 指数退避重试：2, 4, 8 秒
                $delaySeconds = 2 ** $attempts;
                $job->release($delaySeconds);
                Log::channel('push')->warning("百度推送失败，第 {$attempts} 次重试（{$delaySeconds}s 后）: " . $result['msg']);
            } else {
                // 超过最大重试次数，记录日志并删除任务
                $job->delete();
                Log::channel('push')->error('百度推送最终失败，已达到最大重试次数: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            }
        } catch (LibsException $e) {
            // 配置缺失等不可恢复异常，重试无意义
            $job->delete();
            Log::channel('push')->error('百度推送配置异常，任务终止: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $attempts = $job->attempts();

            if ($attempts < self::MAX_ATTEMPTS) {
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
