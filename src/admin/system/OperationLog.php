<?php
declare(strict_types=1);


namespace app\admin\system;

use think\App;
use think\facade\Db;
use app\model\user\UserInfo;
use app\common\traits\CrudTrait;
use app\common\controllers\BaseAdmin;
use app\model\system\SystemOperationLog;
use happy\admin\libs\helper\AuthHelper;
use happy\admin\libs\helper\UserHelper;

class OperationLog extends BaseAdmin
{
    use CrudTrait;

    /**
     * 清空类按钮的确认 + POST 提交脚本（自包含，不依赖额外前端资源）
     * 按钮由 CrudUtil::getButtonHtml() 以 onclick="..." 输出，故脚本内只使用单引号。
     */
    private const CONFIRM_AJAX_JS = <<<'JS'
(function (el) {
    var L = layui.layer, $ = layui.jquery;
    L.confirm(el.getAttribute('data-tip') || '确认执行此操作？', {
        icon: 3, title: '危险操作确认', btn: ['确认执行', '取消']
    }, function (i) {
        L.close(i);
        var ld = L.load(1);
        $.ajax({
            url: el.getAttribute('data-url'), type: 'POST', dataType: 'json',
            success: function (r) {
                L.close(ld);
                L.msg(r.msg, {icon: r.code == 1 ? 1 : 2});
                if (r.code == 1) { setTimeout(function () { location.reload(); }, 800); }
            },
            error: function () { L.close(ld); L.msg('网络错误'); }
        });
    });
    return false;
})(this)
JS;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemOperationLog();
        $this->setParams();
    }

    protected function setParams(): void
    {
        $this->tables['title'] = '操作日志';
        // 定义表格字段（仅列定义；按钮配置必须放在 tables 顶层，
        // CrudTrait::index() 读取的是 $this->tables['top_button'] / ['right_button']）
        $this->tables['fields'] = [
            [
                'field' => 'id',
                'title' => 'ID',
                'width' => 80,
                'sort' => true,
            ], [
                'field' => 'username',
                'title' => '用户',
            ],
            [
                'field' => 'node',
                'title' => '操作节点',
                'width' => 150,
            ],
            [
                'field' => 'desc',
                'title' => '描述',
            ],
            [
                'field' => 'ip',
                'title' => 'IP地址',
                'width' => 150,
            ],
            [
                'field' => 'user_agent',
                'title' => 'User-Agent',
            ],
            [
                'field' => 'create_time',
                'title' => '操作时间',
                'width' => 200,
                'sort' => true,
            ],
        ];
        $this->tables['top_button']   = $this->buildTopButtons();
        $this->tables['right_button'] = [
            ['event' => 'del'],
        ];
        $this->forms['fields'] = [];
        // 定义搜索条件
        $this->search = [
            'id#=#id',
            'uid#=#uid',
            'node#like#node',
            'create_time#between#create_time',
        ];
    }

    /**
     * 顶部按钮：保留批量删除，新增两个清空入口
     * 「清空全部」仅超管可见；方法内部仍会做硬性校验，前端隐藏不构成安全边界。
     * @return array
     */
    protected function buildTopButtons(): array
    {
        $buttons = [
            ['event' => 'del'],
            [
                'event' => 'clearMine',
                'name'  => '清空我的记录',
                'class' => 'layui-btn-warm',
                'extra' => [
                    'data-url' => urls('clearMine'),
                    'data-tip' => '确定清空当前账号的全部操作日志吗？此操作不可恢复！',
                    'onclick'  => self::CONFIRM_AJAX_JS,
                ],
            ],
        ];

        if (AuthHelper::instance()->isAuthAdmin()) {
            $buttons[] = [
                'event' => 'clearAll',
                'name'  => '清空全部记录',
                'class' => 'layui-btn-danger',
                'extra' => [
                    'data-url' => urls('clearAll'),
                    'data-tip' => '确定清空【全部账号】的操作日志吗？此操作不可恢复！',
                    'onclick'  => self::CONFIRM_AJAX_JS,
                ],
            ];
        }

        return $buttons;
    }

    /**
     * 清空全部操作日志（仅超级管理员）
     *
     * 安全说明：带 @auth true 使节点进入权限体系；但即便管理员被显式授权该节点，
     * 方法内仍以 isAuthAdmin() 做硬性拦截，授权不越权。
     * @auth true
     * @return void
     */
    public function clearAll(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        if (!AuthHelper::instance()->isAuthAdmin()) {
            $this->error('仅超级管理员可清空全部操作日志');
        }

        try {
            $total = Db::name('system_operation_log')->count();
            // 独立 Query，避免聚合查询残留的 options 影响删除条件
            Db::name('system_operation_log')->where('id', '>', 0)->delete();
        } catch (\Throwable $e) {
            $this->error('清空失败：' . $e->getMessage());
        }
        // success()/error() 均以抛出 HttpResponseException 中断，不可放进 try 内
        $this->success("已清空全部操作日志（共 {$total} 条）");
    }

    /**
     * 清空当前登录账号的操作日志
     * @auth true
     * @return void
     */
    public function clearMine(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        $uid = (int)UserHelper::instance()->getUserId();
        if ($uid <= 0) {
            $this->error('请先登录');
        }

        try {
            $total = Db::name('system_operation_log')->where('uid', $uid)->count();
            // 独立 Query，避免聚合查询残留的 options 影响删除条件
            Db::name('system_operation_log')->where('uid', $uid)->delete();
        } catch (\Throwable $e) {
            $this->error('清空失败：' . $e->getMessage());
        }
        // success()/error() 均以抛出 HttpResponseException 中断，不可放进 try 内
        $this->success("已清空当前账号的操作日志（共 {$total} 条）");
    }

    /**
     * 处理列表数据
     * @param array $data
     * @return void
     */
    protected function _index_list_filter(array &$data): void
    {
        // 确保 data['data'] 存在并且是一个数组
        if (isset($data['data']) && is_array($data['data'])) {
            // 提取当前分页数据中的所有 uid
            $userIds = array_unique(array_column($data['data'], 'uid'));

            // 如果有 uid，查询对应的用户名
            $userNames = [];
            if (!empty($userIds)) {
                $userNames = UserInfo::whereIn('id', $userIds)->column('username', 'id');
            }

            // 绑定用户名；账号已删除等场景回退为「未知用户」，并始终保留 uid
            foreach ($data['data'] as &$v) {
                $uid  = $v['uid'] ?? 0;
                $name = $userNames[$uid] ?? '';
                $v['username'] = $name !== ''
                    ? $name . '(id：' . $uid . ')'
                    : '未知用户(id：' . $uid . ')';
            }
            unset($v); // 解除引用
        }
    }
}
