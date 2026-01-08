<?php

namespace think\oauth;

use think\facade\Log;

class Http
{
    /**
     * 全局默认配置
     */
    protected static array $defaults = [
        'timeout' => 10,
        'connect_timeout' => 5,
        'user_agent' => 'ThinkPHP-OAuth-Client/1.1',
        'max_retries' => 1, // 失败时最多重试 1 次
    ];

    /**
     * GET 请求
     *
     * @param string $url
     * @param array $options ['timeout', 'headers', 'query']
     * @return array ['code', 'headers', 'body']
     * @throws \Exception
     */
    public static function get(string $url, array $options = []): array
    {
        if (!empty($options['query'])) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($options['query']);
        }

        return self::request('GET', $url, $options);
    }

    /**
     * POST 请求（application/x-www-form-urlencoded）
     *
     * @param string $url
     * @param array $data 表单数据
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public static function post(string $url, array $data = [], array $options = []): array
    {
        $options['body'] = http_build_query($data);
        $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        return self::request('POST', $url, $options);
    }

    /**
     * 通用 HTTP 请求
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return array ['code', 'headers', 'body']
     * @throws \Exception
     */
    public static function request(string $method, string $url, array $options = []): array
    {
        if (!extension_loaded('curl')) {
            throw new \Exception('cURL extension is required.');
        }

        $timeout = $options['timeout'] ?? self::$defaults['timeout'];
        $connectTimeout = $options['connect_timeout'] ?? self::$defaults['connect_timeout'];
        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? '';
        $maxRetries = $options['max_retries'] ?? self::$defaults['max_retries'];

        $attempt = 0;
        while ($attempt <= $maxRetries) {
            $ch = curl_init();

            $curlOptions = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => $headers['User-Agent'] ?? self::$defaults['user_agent'],
            ];

            if ($method === 'POST') {
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = $body;
            } elseif ($method !== 'GET') {
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
            }

            // 设置 headers
            $curlHeaders = [];
            foreach ($headers as $key => $value) {
                if ($key !== 'User-Agent') {
                    $curlHeaders[] = "{$key}: {$value}";
                }
            }
            if (!empty($curlHeaders)) {
                $curlOptions[CURLOPT_HTTPHEADER] = $curlHeaders;
            }

            curl_setopt_array($ch, $curlOptions);

            $response = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // 成功 or 最后一次重试失败才抛出
            if ($errno === 0 && $httpCode < 400) {
                return compact('httpCode', 'response');
            }

            $attempt++;
            if ($attempt <= $maxRetries) {
                // 可选：记录重试日志
                Log::warning("HTTP request retry {$attempt} for {$url}, error: {$error} (code: {$httpCode})");
                usleep(500000); // 等待 0.5 秒
            }
        }

        $msg = "HTTP {$method} to {$url} failed after {$attempt} attempts";
        if ($errno) {
            $msg .= ", cURL error ({$errno}): {$error}";
        } else {
            $msg .= ", HTTP status: {$httpCode}";
        }

        Log::error($msg);
        throw new \Exception($msg);
    }
}
