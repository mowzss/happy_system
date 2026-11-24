<?php
declare (strict_types=1);

namespace app\api\cms;

use app\common\controllers\BaseApi;
use think\facade\Db;

/**
 * 通用附表查询（公开，白名单表）
 *
 * 供首页展示"诗人推荐 / 诗词标签"等附表数据。
 * 仅允许白名单内的表名与字段，杜绝任意表穿透。
 *
 * 注意：$this->json() 内部抛 HttpResponseException 中断响应，
 *       不可放进 try/catch，否则会被自己的 catch 吞掉。
 *
 * 端点：GET /api.php/cms/table?name=poetry_author&rows=6&order=list
 */
class Table extends BaseApi
{
    /** 允许查询的表 → 允许返回的字段 */
    private const TABLES = [
        'poetry_author' => ['id', 'title', 'alias', 'avatar', 'group_name', 'dynasty_id', 'list', 'view'],
        'poetry_tag'    => ['id', 'title', 'count', 'view', 'list'],
        // 诗人作品：按 author_id 查内容表（特殊处理，见 index()）
        'poetry_author_contents' => ['id', 'title', 'cid', 'dynasty_id', 'author_id', 'view'],
    ];

    private const ORDER_FIELDS = ['id', 'list', 'view', 'count', 'create_time'];

    public function index(): void
    {
        $name = (string)$this->request->param('name', '');
        if (!isset(self::TABLES[$name])) {
            $this->json([], 400, '不允许的数据表');
        }

        $rows = (int)$this->request->param('rows', 10);
        $rows = min(max($rows, 1), 100);
        $order = (string)$this->request->param('order', 'list');
        if (!in_array($order, self::ORDER_FIELDS, true)) {
            $order = 'list';
        }
        $by = strtolower((string)$this->request->param('by', 'desc')) === 'asc' ? 'asc' : 'desc';

        $id = (int)$this->request->param('id', 0);

        $items = [];
        try {
            if ($name === 'poetry_author_contents') {
                // 诗人作品：id 参数即 author_id，查 poetry 内容表
                $items = Db::name('poetry_content_1')
                    ->where('status', 1)
                    ->where('author_id', $id)
                    ->field(self::TABLES[$name])
                    ->order('id', 'desc')
                    ->limit($rows)
                    ->select()
                    ->toArray();
            } else {
                $query = Db::name($name)->where('status', 1)->field(self::TABLES[$name]);
                if ($id > 0) {
                    $row = $query->where('id', $id)->find();
                    $items = $row ? [$row] : [];
                } else {
                    $items = $query->order($order, $by)->limit($rows)->select()->toArray();
                }
            }
        } catch (\Throwable $e) {
            $items = [];
        }
        $this->json($items, 0);
    }
}
