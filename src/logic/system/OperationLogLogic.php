<?php
declare(strict_types=1);


namespace app\logic\system;

use happy\admin\libs\helper\NodeHelper;
use happy\admin\libs\helper\UserHelper;
use happy\admin\libs\helper\OperationLogHelper;

/**
 * 后台操作日志自动记录器（无状态工具类）
 *
 * 由 CommonService 监听 AdminControllerInit 事件触发，覆盖全部后台控制器，
 * 无需在各模块逐一埋点。记录规则：
 *  1) 仅记录已登录用户的写操作：POST 请求，或方法名命中写动词的 GET 请求
 *     （框架中部分清理/刷新类操作走 GET）；
 *  2) 排除列表渲染、选项拉取等高频只读接口，避免日志膨胀；
 *  3) 描述由「中文动作 + 权限节点 + 脱敏参数摘要」自动生成；
 *  4) 密码、令牌、密钥等敏感字段一律脱敏，正文类长文本截断；
 *  5) 全流程异常隔离，日志写入失败不影响业务响应。
 *
 * 如需为特定操作指定更有语义的描述，仍可在控制器中直接调用 sys_opt_log($desc)。
 */
class OperationLogLogic
{
    /** 高频只读接口，不记录 */
    private const EXCLUDED_ACTIONS = [
        'index', 'main', 'getoptionlist', 'geticons', 'getlist', 'gettree',
        'getdetail', 'check', 'captcha', 'verify', 'login', 'logout',
    ];

    /** GET 型写操作动词（方法名包含即视为写操作） */
    private const WRITE_ACTION_WORDS = [
        'clear', 'clean', 'delete', 'remove', 'refresh', 'reset', 'flush',
        'truncate', 'sync', 'push', 'build', 'rebuild', 'run', 'execute',
        'import', 'install', 'uninstall', 'enable', 'disable', 'start',
        'stop', 'send', 'restore', 'migrate', 'revoke', 'approve', 'reject',
    ];

    /** 方法名 → 中文动作 */
    private const ACTION_LABELS = [
        'add'             => '新增',
        'edit'            => '编辑',
        'delete'          => '删除',
        'quickedit'       => '快捷编辑',
        'save'            => '保存',
        'update'          => '更新',
        'upload'          => '上传',
        'clearall'        => '清空全部操作日志',
        'clearmine'       => '清空当前账号操作日志',
        'clearduplicates' => '清理重复记录',
        'refreshindex'    => '刷新索引',
        'reset'           => '重置',
        'sync'            => '同步',
        'import'          => '导入',
        'execute'         => '执行',
    ];

    /** 敏感参数关键字（子串匹配，命中即打码） */
    private const SENSITIVE_KEYS = [
        'password', 'passwd', 'pwd', 'token', 'secret', 'private_key',
        'api_key', 'app_key', 'access_key', 'encryption_key', 'totp',
        'signature', 'sign', 'captcha', 'verify_code', 'credit_card',
    ];

    /** 单个参数值最大展示长度 */
    private const MAX_PARAM_LEN = 60;
    /** 参数摘要总长上限 */
    private const MAX_PARAM_SUMMARY = 300;

    /**
     * 自动记录一次后台操作
     * @return void
     */
    public static function record(): void
    {
        try {
            $request = request();
            $action  = strtolower((string)$request->action());

            // 只记录写操作；纯展示/查询类请求直接跳过
            $isPost      = $request->isPost();
            $isWriteVerb = self::isWriteAction($action);
            if (!$isPost && !$isWriteVerb) {
                return;
            }
            if (in_array($action, self::EXCLUDED_ACTIONS, true)) {
                return;
            }

            // 审计日志必须可归属到账号，未登录（如登录接口）不记录
            $uid = (int)UserHelper::instance()->getUserId();
            if ($uid <= 0) {
                return;
            }

            $node   = NodeHelper::instance()->getThisNode();
            $desc   = self::buildDesc($action, $node, $request->param());
            OperationLogHelper::log($desc);
        } catch (\Throwable $e) {
            // 自动记录属于旁路能力，任何异常都不能影响主请求
            try {
                app()->log->error('[操作日志] 自动记录失败：' . $e->getMessage());
            } catch (\Throwable $ignored) {
            }
        }
    }

    /**
     * 生成可读描述
     * @param string $action
     * @param string $node
     * @param array  $params
     * @return string
     */
    private static function buildDesc(string $action, string $node, array $params): string
    {
        $label = self::ACTION_LABELS[$action] ?? self::guessLabel($action);
        $desc  = $label . ' [' . $node . ']';

        $summary = self::buildParamSummary($params);
        if ($summary !== '') {
            $desc .= ' | ' . $summary;
        }
        return $desc;
    }

    /**
     * 按写动词推断中文动作
     * @param string $action
     * @return string
     */
    private static function guessLabel(string $action): string
    {
        $map = [
            'clear'    => '清空', 'clean'   => '清理', 'delete'  => '删除',
            'remove'   => '移除', 'refresh' => '刷新', 'reset'   => '重置',
            'sync'     => '同步', 'push'    => '推送', 'build'   => '生成',
            'run'      => '执行', 'execute' => '执行', 'import'  => '导入',
            'install'  => '安装', 'enable'  => '启用', 'disable' => '禁用',
            'send'     => '发送', 'restore' => '恢复', 'approve' => '审核通过',
            'reject'   => '驳回',
        ];
        foreach ($map as $word => $label) {
            if (str_contains($action, $word)) {
                return $label;
            }
        }
        return '执行';
    }

    /**
     * 方法名是否命中写操作动词
     * @param string $action
     * @return bool
     */
    private static function isWriteAction(string $action): bool
    {
        foreach (self::WRITE_ACTION_WORDS as $word) {
            if (str_contains($action, $word)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 生成脱敏后的参数摘要
     * @param array $params
     * @return string
     */
    private static function buildParamSummary(array $params): string
    {
        $parts = [];
        $length = 0;

        foreach ($params as $key => $value) {
            $key = (string)$key;
            if ($key === '' || $key === 's') {
                continue;
            }

            if (self::isSensitive($key)) {
                $segment = $key . '=***';
            } elseif (is_array($value)) {
                $segment = $key . '=[' . count($value) . '项]';
            } elseif (is_object($value)) {
                $segment = $key . '=[对象]';
            } else {
                $text = (string)$value;
                if (mb_strlen($text) > self::MAX_PARAM_LEN) {
                    $text = mb_substr($text, 0, self::MAX_PARAM_LEN) . '…';
                }
                $segment = $key . '=' . $text;
            }

            $length += mb_strlen($segment) + 2;
            if ($length > self::MAX_PARAM_SUMMARY) {
                $parts[] = '…';
                break;
            }
            $parts[] = $segment;
        }

        return implode(', ', $parts);
    }

    /**
     * 是否为敏感参数名
     * @param string $key
     * @return bool
     */
    private static function isSensitive(string $key): bool
    {
        $key = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $word) {
            if (str_contains($key, $word)) {
                return true;
            }
        }
        return false;
    }
}
