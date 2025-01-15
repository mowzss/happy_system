<?php

// 应用公共文件
use app\common\util\SendMailUtil;

if (!function_exists('static_version')) {
    function static_version()
    {
        $type = sys_config('static_cache_time');
        switch ($type) {
            case 'y':
                return date('Y');
            case 'm':
                return date('Ym');
            case 'd':
                return date('ym.d');
            case 'h':
                return date('ym.dH');
            case 'i':
                return date('ym.dH.i');
            case 's':
                return date('ym.dH.is');
            default:
                return $type;
        }
    }
}
if (!function_exists('fun')) {
    /**
     * 动态调用app\common\fun下的类及方法
     *
     * @param string $className 类名
     * @param string $method 方法名
     * @param mixed ...$params 参数列表
     * @return mixed
     * @throws \Exception
     */
    function fun(string $className, string $method, ...$params): mixed
    {
        // 构建完整的类名，包含命名空间
        $fullClassName = '\\app\\common\\fun\\' . ucfirst($className);
        if (!class_exists($fullClassName)) {
            throw new \Exception("Class {$fullClassName} not found.");
        }
        // 检查方法是否存在
        if (!method_exists($fullClassName, $method) && !method_exists($fullClassName, '__callStatic')) {
            throw new \Exception("Method {$method} not found in class {$fullClassName}.");
        }
        // 使用反射获取方法信息
        $reflectionMethod = new \ReflectionMethod($fullClassName, $method);
        // 判断是否为静态方法
        if ($reflectionMethod->isStatic()) {
            // 调用静态方法
            return $reflectionMethod->invokeArgs((object)null, $params);
        } else {
            // 创建类的实例并调用非静态方法
            $instance = new $fullClassName();
            return $reflectionMethod->invokeArgs($instance, $params);
        }
    }
}
if (!function_exists('get_user_avatar')) {
    function get_user_avatar($uid = '')
    {
        if (empty($uid)) {
            $uid = \mowzs\lib\helper\UserHelper::instance()->getUserId();
        }
    }
}
if (!function_exists('get_hello')) {
    /**
     * 问候语
     * @param string $word 欢迎语
     * @param string $tip 关怀语
     * @return string
     */
    function get_hello($word = '欢迎回来！', $tip = '夜深了，注意身体哦！')
    {
        $h = date('H');
        if ($h < 9) {
            $time = "早上好！";
        } else {
            if ($h < 12) {
                $time = "上午好！";
            } else {
                if ($h < 14) {
                    $time = "中午好！";
                } else {
                    if ($h < 18) {
                        $time = "下午好！";
                    } else {
                        if ($h < 24) {
                            $time = "晚上好！";
                        } else {
                            $time = "你好！";
                        }
                    }
                }
            }
        }
        $time = $time . $word;
        if ($h < 6) {
            $time = $tip;
        }
        return $time;
    }
}
if (!function_exists('event_listen')) {

    /**
     * @param object|string $event
     * @param mixed $params
     * @param bool $once
     * @return void
     */
    function event_listen(object|string $event, mixed &$params = [], bool $once = false): void
    {
        \mowzs\lib\helper\EventHelper::instance()->listen($event, $params, $once);
    }
}
if (!function_exists('get_links')) {
    /**
     * @param int|string $cid
     * @return mixed
     * @throws Throwable
     */
    function get_links(int|string $cid = 1): mixed
    {
        return \app\service\system\LinksService::instance()->getLinksByCid($cid);
    }
}

if (!function_exists('get_nav')) {
    /**
     * 获取导航
     * @param string $dir 分类 pc wap wap_footer pc_user wap_user wap_user_footer
     * @return mixed
     * @throws Throwable
     */
    function get_nav(string $dir = 'pc'): mixed
    {
        return \app\service\system\NavService::instance()->getNavByDir($dir);
    }
}
if (!function_exists('get_word')) {
    /**
     * 截取指定长度的字符串，并在超出长度时添加省略号
     *
     * @param string $string 需要截取的字符串
     * @param int $length 截取的最大字符数
     * @param bool $more 是否在超出长度时添加省略号，默认为 true
     * @param string $dot 省略号的内容，默认为 '..'
     * @param string $encoding 字符串编码，默认为 'UTF-8'
     * @return string 截取后的字符串
     */
    function get_word(string $string, int $length, bool $more = true, string $dot = '..', string $encoding = 'UTF-8'): string
    {
        // 如果字符串长度小于或等于指定长度，直接返回原字符串
        if (mb_strlen($string, $encoding) <= $length) {
            return $string;
        }

        // 解码 HTML 实体，防止截断时破坏 HTML 语法
        $decodedString = htmlspecialchars_decode($string, ENT_QUOTES);

        // 使用 mb_substr 截取指定长度的字符串
        $truncatedString = mb_substr($decodedString, 0, $length, $encoding);

        // 如果需要添加省略号
        if ($more) {
            $truncatedString .= $dot;
        }

        // 重新编码 HTML 实体，确保输出的安全性
        return htmlspecialchars($truncatedString, ENT_QUOTES, $encoding);
    }
}
if (!function_exists('del_html')) {
    /**
     * 清除 HTML 代码并优化文章内容
     *
     * @param string $content 需要清理的 HTML 内容
     * @param array|null $allowedTags 可选参数，允许保留的 HTML 标签，默认为空数组（即清理所有标签）
     * @return string 清理后的纯文本内容
     */
    function del_html(string $content = '', ?array $allowedTags = []): string
    {
        // 如果内容为空，直接返回空字符串
        if (empty($content)) {
            return '';
        }
        // 1. 清除 JavaScript 和 CSS 样式
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);  // 清除 <script> 标签
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);    // 清除 <style> 标签

        // 2. 清除所有 HTML 标签，但保留指定的标签
        if (empty($allowedTags)) {
            // 如果没有指定允许的标签，清除所有 HTML 标签
            $content = strip_tags($content);
        } else {
            // 如果指定了允许的标签，只清除不允许的标签
            $allowedTagsString = implode('', $allowedTags);
            $content = strip_tags($content, $allowedTagsString);
        }

        // 3. 转换 HTML 实体为对应的字符
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');  // 解码 HTML 实体

        // 4. 处理空白字符
        $content = str_replace(["\r", "\n", '　'], ' ', $content);  // 替换回车、换行和全角空格为半角空格
        $content = preg_replace('/\s+/', ' ', $content);            // 将多个连续的空白字符替换为一个空格
        $content = trim($content);                                  // 去除首尾空白字符

        return $content;
    }
}
if (!function_exists('format_datetime')) {
    /**
     * 日期格式标准输出
     * @param int|string $datetime 输入日期
     * @param string $format 输出格式
     * @return string
     */
    function format_datetime(int|string $datetime, string $format = 'Y年m月d日 H:i:s'): string
    {
        if (empty($datetime)) {
            return '-';
        } elseif (is_numeric($datetime)) {
            return date($format, intval($datetime));
        } elseif ($timestamp = strtotime($datetime)) {
            return date($format, $timestamp);
        } else {
            return $datetime;
        }
    }
}
if (!function_exists('format_view')) {
    /**
     * 格式化浏览量
     *
     * @param int $int 浏览量
     * @param bool $decimals 是否保留一位小数，默认为 false
     * @return string 格式化后的浏览量字符串
     */
    function format_view(int $int, bool $decimals = false): string
    {
        // 处理负数和零的情况
        if ($int <= 0) {
            return '0';
        }

        // 根据浏览量大小进行格式化
        if ($int >= 10000) {
            // 超过 1 万，格式化为 "万"
            $value = $decimals ? round($int / 10000, 1) : round($int / 10000);
            return $value . 'w';
        } elseif ($int >= 1000) {
            // 超过 1 千，格式化为 "千"
            $value = $decimals ? round($int / 1000, 1) : round($int / 1000);
            return $value . 'k';
        } else {
            // 小于 1 千，直接返回原始数字
            return (string)$int;
        }
    }
}
if (!function_exists('format_time')) {
    /**
     * 时间戳格式化
     *
     * @param int|string $time 时间戳或日期字符串
     * @param bool|string $format 输出格式。如果为 true，则按 "刚刚"、"几分钟前" 等格式显示；否则按指定的日期格式显示。
     * @param string $longFormat 当 $format 为 true 且时间超过一个月时，使用的日期格式。
     * @return string 格式化后的时间字符串
     */
    function format_time(int|string $time = '', bool|string $format = 'Y-m-d', string $longFormat = 'Y-m-d'): string
    {
        // 如果传入的不是时间戳，尝试将其转换为时间戳
        if (!is_numeric($time)) {
            $time = strtotime($time);
        }

        // 如果时间戳无效，返回空字符串
        if ($time === false) {
            return '';
        }

        // 获取当前时间和时间差
        $currentTime = time();
        $timeDifference = $currentTime - intval($time);

        // 如果需要相对时间格式
        if ($format === true) {
            // 定义时间间隔单位
            $intervals = [
                'year' => 3600 * 24 * 365,
                'month' => 3600 * 24 * 30,
                'day' => 3600 * 24,
                'hour' => 3600,
                'minute' => 60,
                'second' => 1
            ];

            // 遍历时间间隔单位，找到合适的时间描述
            foreach ($intervals as $unit => $seconds) {
                if ($timeDifference >= $seconds) {
                    $count = intval($timeDifference / $seconds);
                    switch ($unit) {
                        case 'year':
                            return $count . '年前';
                        case 'month':
                            return $count . '个月前';
                        case 'day':
                            return $count . '天前';
                        case 'hour':
                            return $count . '小时前';
                        case 'minute':
                            return $count . '分钟前';
                        case 'second':
                            return $timeDifference < 60 ? '刚刚' : '1分钟前';
                    }
                }
            }

            // 如果时间差小于 60 秒，返回 "刚刚"
            return '刚刚';
        }

        // 如果不需要相对时间格式，直接返回指定格式的日期
        return date($format, $time);
    }
}
if (!function_exists('sys_config')) {
    /**
     * @param string|null $name 配置名称
     * @param mixed|null $default
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function sys_config(?string $name = null, mixed $default = null): mixed
    {
        return \app\model\system\SystemConfig::getConfigValue($name, $default);
    }
}
if (!function_exists('sys_opt_log')) {
    /**
     * @param string $desc
     * @return bool
     */
    function sys_opt_log(string $desc = ''): bool
    {
        return \mowzs\lib\helper\OperationLogHelper::log($desc);
    }
}
if (!function_exists('download_file')) {
    /**
     * 下载远程文件并保存到指定位置
     *
     * @param string $url 远程文件的URL
     * @param string|null $savePath 保存路径（可选）
     * @return array 成功返回文件信息，失败抛出异常
     * @throws Exception
     */
    function download_file(string $url, ?string $savePath = null): array
    {
        // 创建 RemoteFileService 实例
        $service = new \app\common\util\RemoteFileUtil();
        // 调用服务类的方法进行文件下载和保存
        return $service->downloadAndSave($url, $savePath);
    }
}
if (!function_exists('aurl')) {
    /**
     * 强制后台链接
     * @param string $url
     * @param string|array $vars
     * @param bool $suffix
     * @param bool $domain
     * @return string
     */
    function aurl(string $url = '', string|array $vars = [], bool $suffix = true, bool $domain = false): string
    {
        return urls($url, $vars, $suffix, $domain, '/admin.php');
    }
}
if (!function_exists('hurl')) {
    /**
     * 强制前台链接，并移除 URL 中的 /xxxx.php/
     *
     * @param string $url 原始 URL
     * @param string|array $vars URL 参数数组
     * @param bool $suffix 是否添加 URL 后缀，默认为 true
     * @param bool $domain 是否返回完整域名，默认为 false
     * @return string 处理后的 URL
     */
    function hurl(string $url = '', string|array $vars = [], bool $suffix = true, bool $domain = false): string
    {
        // 调用 urls 函数生成初始 URL
        $url = urls($url, $vars, $suffix, $domain, '/index.php');

        // 解析 URL
        $parsedUrl = parse_url($url);

        // 初始化路径
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';

        // 移除 /xxxx.php/ 从路径中
        // 使用正则表达式匹配任何 .php 文件名并移除
        $path = preg_replace('#/[^/]+\.php/#i', '/', $path);

        // 重构 URL
        $reconstructedUrl = '';

        // 如果有协议和主机（即完整域名），则保留
        if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
            $reconstructedUrl .= $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $reconstructedUrl .= ':' . $parsedUrl['port'];
            }
        }

        // 添加路径
        $reconstructedUrl .= $path;

        // 添加查询参数
        if (isset($parsedUrl['query'])) {
            $reconstructedUrl .= '?' . $parsedUrl['query'];
        }

        // 添加片段标识符（#）
        if (isset($parsedUrl['fragment'])) {
            $reconstructedUrl .= '#' . $parsedUrl['fragment'];
        }

        return $reconstructedUrl;
    }
}
if (!function_exists('urls')) {
    /**
     * 通用链接
     * @param string $url
     * @param array $vars
     * @param bool $suffix
     * @param bool $domain
     * @param $root
     * @return string
     */
    function urls(string $url = '', array|string $vars = [], bool $suffix = true, bool $domain = false, $root = ''): string
    {

        // 分割路径为模块、控制器和方法
        $pathInfo = explode('/', trim($url, '/'));
        $module = '';
        $controller = '';
        $action = '';
        // 判断是否指定了模块
        if (count($pathInfo) >= 3) {
            list($module, $controller, $action) = $pathInfo;
        } elseif (count($pathInfo) === 2) {
            list($controller, $action) = $pathInfo;
        } elseif (count($pathInfo) === 1) {
            $action = $pathInfo[0];
        }
        // 如果没有指定模块，使用当前模块
        if (empty($module)) {
            $module = request()->layer();
        }
        // 如果没有指定控制器，使用当前控制器
        if (empty($controller)) {
            $controller = \think\helper\Str::snake(request()->controller(false, true));
        }
        // 如果没有指定方法，使用当前方法
        if (empty($action)) {
            $action = request()->action();
        }
        // 构建最终的 URL
        $finalUrl = $module . '/' . $controller . '/' . $action;

        if (is_string($vars)) {
            parse_str($vars, $vars);
        }
        $url = \think\facade\Route::buildUrl($finalUrl, $vars)->suffix($suffix)->root($root)->domain($domain)->build();
        if (!empty($root)) {
            // 找到 $a 在 $url 中的位置
            $pos = strpos($url, $root);

            if ($pos !== false) {
                // 截取 $a 之后的部分
                $afterA = substr($url, $pos + strlen($root));

                // 查找第一个出现的 .php 位置
                $phpPos = strpos($afterA, '.php');

                if ($phpPos !== false) {
                    // 检查 .php 前是否有一个斜杠
                    $slashBeforePhp = ($phpPos > 0 && $afterA[$phpPos - 1] === '/') ? true : false;

                    // 构建新的 URL
                    if ($slashBeforePhp) {
                        // 如果有斜杠，则从斜杠开始移除，直到 .php 结束
                        $newUrl = $root . substr($afterA, $phpPos + 4); // 4 是 '/.php' 的长度
                    } else {
                        // 如果没有斜杠，只移除 .php 部分
                        $newUrl = $root . substr($afterA, $phpPos + 4); // 4 是 '.php' 的长度
                    }

                    return $newUrl;
                }
            }
        }
        return $url;
    }
}

if (!function_exists('str2arr')) {
    /**
     * 字符串转数组
     * @param string $text 待转内容
     * @param string $separ 分隔字符
     * @param ?array $allow 限定规则
     * @return array
     */
    function str2arr(string $text, string $separ = ',', ?array $allow = null): array
    {
        $items = [];
        foreach (explode($separ, trim($text, $separ)) as $item) {
            if ($item !== '' && (!is_array($allow) || in_array($item, $allow))) {
                $items[] = trim($item);
            }
        }
        return $items;
    }
}
if (!function_exists('arr2str')) {
    /**
     * 数组转字符串
     * @param array $data 待转数组
     * @param string $separ 分隔字符
     * @param ?array $allow 限定规则
     * @return string
     */
    function arr2str(array $data, string $separ = ',', ?array $allow = null): string
    {
        foreach ($data as $key => $item) {
            if ($item === '' || (is_array($allow) && !in_array($item, $allow))) {
                unset($data[$key]);
            }
        }
        return $separ . join($separ, $data) . $separ;
    }
}
if (!function_exists('p')) {
    /**
     * 打印输出数据到文件
     * @param mixed $data 输出的数据
     * @param boolean $new 强制替换文件
     * @param ?string $file 保存文件名称
     * @return false|int
     */
    function p(mixed $data, bool $new = false, ?string $file = null)
    {
        ob_start();
        var_dump($data);
        $output = preg_replace('/]=>\n(\s+)/m', '] => ', ob_get_clean());
        if (is_null($file)) {
            $file = app()->getRuntimePath() . date('Ymd') . '.log';
        } elseif (!preg_match('#[/\\\\]+#', $file)) {
            $file = app()->getRuntimePath() . "/{$file}.log";
        }
        is_dir($dir = dirname($file)) or mkdir($dir, 0777, true);
        return $new ? file_put_contents($file, $output) : file_put_contents($file, $output, FILE_APPEND);
    }
}
if (!function_exists('get_lay_table_id')) {
    /**
     * @return string
     */
    function get_lay_table_id(): string
    {
        return 'table-' . md5(request()->url());
    }

}
if (!function_exists('send_email')) {

    /**
     * 发送普通邮件的助手函数.
     *
     * @param array|string $to 收件人邮箱地址或数组
     * @param string $subject 邮件主题
     * @param string $body 邮件正文
     * @param bool $isHtml 是否为HTML格式
     * @param array $attachments 附件列表
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    function send_email(array|string $to, string $subject, string $body, bool $isHtml = false, array $attachments = []): bool
    {
        $mailUtil = new SendMailUtil();
        if (is_array($to)) {
            foreach ($to as $recipient) {
                $mailUtil->addTo($recipient);
            }
        } else {
            $mailUtil->addTo($to);
        }
        $mailUtil->setSubject($subject)
            ->setBody($body, $isHtml);

        // 添加附件（如果有）
        foreach ($attachments as $attachment) {
            $mailUtil->addAttachment($attachment['path'], $attachment['name'] ?? null);
        }

        return $mailUtil->send();
    }
}
if (!function_exists('send_code_email')) {
    /**
     * 发送验证码邮件的助手函数.
     *
     * @param string $email 收件人邮箱地址
     * @param string $code 验证码
     * @param string $subject 邮件主题
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    function send_code_email(string $email, string $code, string $subject = '注意查收！您申请的验证码'): bool
    {
        $mailUtil = new SendMailUtil();
        return $mailUtil->sendVerificationCode($email, $code, $subject);
    }
}
