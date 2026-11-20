<?php
declare (strict_types=1);

namespace app\common\util;

use think\Model;
use think\db\Query;

/**
 * CRUD 安全查询构建器
 *
 * 集中处理列表查询中的搜索条件、关联预加载、排序、分页校验，
 * 供 CrudTrait 及重写了查询逻辑的控制器（ContentAdmin / Nav 等）共用。
 *
 * 设计原则：
 * 1. 所有来自请求的参数一律视为不可信，必须经过白名单或类型校验；
 * 2. 搜索配置中声明的字段名来自服务端配置，请求只提供值；
 * 3. 多字段 OR 必须使用闭包分组，避免污染外层 AND 条件。
 */
final class CrudQuery
{
    /** 默认分页上限，防止超大 limit 拖库 */
    public const DEFAULT_MAX_LIMIT = 200;

    /**
     * 解析 search 配置项（格式：字段名#操作符#请求参数名）
     *
     * @return array{0:string,1:string,2:string}|null 格式非法时返回 null
     */
    public static function parseSearchConfig(string $config): ?array
    {
        $parts = explode('#', $config);
        if (count($parts) < 3) {
            return null;
        }
        $fields = trim($parts[0]);
        $operator = strtoupper(trim($parts[1]));
        $paramKey = trim($parts[2]);
        if ($fields === '' || $operator === '' || $paramKey === '') {
            return null;
        }
        return [$fields, $operator, $paramKey];
    }

    /**
     * 判断区间两端点是否为「日期字符串」而非时间戳
     *
     * daterange 控件提交 2024-01-01 这类日期；若端点为纯数字时间戳，
     * 应保持原生 BETWEEN，避免被 ORM 重复 strtotime 转换。
     *
     * @param array $range 仅两个端点
     */
    private static function isDateStringRange(array $range): bool
    {
        foreach ($range as $point) {
            if (!is_string($point) && !is_numeric($point)) {
                return false;
            }
            $point = trim((string)$point);
            if ($point === '') {
                return false;
            }
            // 纯数字视为时间戳，不走时间表达式转换
            if (ctype_digit($point)) {
                return false;
            }
            // 必须能被解析为日期，且含有日期分隔符（排除普通文本）
            if (strtotime($point) === false || !preg_match('/[\/\-\.年月日]/', $point)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 纯日期字符串补当天结束时刻，含时分秒的原样保留
     */
    private static function appendEndOfDay(string $value): string
    {
        $value = trim($value);
        // 已含时间部分（HH:MM 或更完整）则不处理
        if (preg_match('/\d{1,2}:\d{2}/', $value)) {
            return $value;
        }
        $ts = strtotime($value);
        return $ts === false ? $value : date('Y-m-d 23:59:59', $ts);
    }

    /**
     * 应用搜索条件到查询对象
     *
     * @param Model|Query        $model      查询对象
     * @param array<int,string>  $search     搜索配置列表
     * @param array              $requestData 请求参数
     */
    public static function applySearch(Model|Query $model, array $search, array $requestData): Model|Query
    {
        foreach ($search as $config) {
            if (!is_string($config)) {
                continue;
            }
            $parsed = self::parseSearchConfig($config);
            if ($parsed === null) {
                continue;
            }
            [$fields, $operator, $paramKey] = $parsed;

            if (!array_key_exists($paramKey, $requestData)) {
                continue;
            }
            $value = $requestData[$paramKey];
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            // 区间操作符标记：数组值代表两个端点，不能被 reset 成单个值
            $isBetween = in_array($operator, ['BETWEEN', 'NOTBETWEEN', 'BETWEEN TIME', 'NOT BETWEEN TIME'], true);

            // 数组参数只取第一个值，避免 addcslashes 收到数组导致 TypeError（区间查询除外）
            if (is_array($value) && !$isBetween) {
                $value = reset($value);
            }

            // LIKE 值转义：必须同时转义反斜杠，否则用户输入的 \% 可绕过通配符过滤
            if ($operator === 'LIKE' || $operator === 'NOTLIKE') {
                $value = '%' . addcslashes((string)$value, "\\_%") . '%';
            } elseif ($isBetween) {
                // 时间区间：必须恰好两个端点，否则忽略该条件，避免构造异常 SQL
                $range = is_array($value) ? array_values($value) : explode(' - ', (string)$value);
                if (count($range) !== 2) {
                    continue;
                }
                $value = $range;

                // 时间字段在库中通常为 int 时间戳，而 daterange 控件提交日期字符串
                // （如 2024-01-01 - 2024-01-31）。普通 BETWEEN 会把字符串直接与 int 比较导致
                // 条件永远不成立；升级为 BETWEEN TIME 由 ORM 的 parseBetweenTime 转时间戳。
                // 纯数字时间戳区间保持原 BETWEEN，避免被重复转换。
                if ($operator === 'BETWEEN' || $operator === 'NOTBETWEEN') {
                    if (self::isDateStringRange($value)) {
                        // 纯日期端点（如 2025-09-19）会被 ORM 解析为当天 00:00:00，
                        // 导致「选某一天却查不到当天数据」。给结束端点补到当天 23:59:59。
                        $value[1] = self::appendEndOfDay($value[1]);
                        $operator = $operator === 'BETWEEN' ? 'BETWEEN TIME' : 'NOT BETWEEN TIME';
                    }
                }
            }

            $fieldList = array_values(array_filter(array_map('trim', explode('|', $fields)), 'strlen'));
            if ($fieldList === []) {
                continue;
            }

            if (count($fieldList) > 1) {
                // 闭包分组：保证 (A OR B) 整体与外层 AND 条件正确结合
                $model = $model->where(function ($query) use ($fieldList, $operator, $value) {
                    foreach ($fieldList as $index => $field) {
                        if ($index === 0) {
                            $query->where($field, $operator, $value);
                        } else {
                            $query->whereOr($field, $operator, $value);
                        }
                    }
                });
            } else {
                $model = $model->where($fieldList[0], $operator, $value);
            }
        }
        return $model;
    }

    /**
     * 应用关联预加载（白名单校验，未列入白名单的关联一律忽略）
     *
     * @param array<int,string> $allowedRelations 允许预加载的关联名；空数组表示禁止全部
     */
    public static function applyWith(Model|Query $query, mixed $withParam, array $allowedRelations): Model|Query
    {
        if (empty($withParam) || $allowedRelations === []) {
            return $query;
        }
        $relations = is_array($withParam) ? $withParam : explode(',', (string)$withParam);
        foreach ($relations as $relation) {
            $relation = is_string($relation) ? trim($relation) : '';
            if ($relation !== '' && in_array($relation, $allowedRelations, true)) {
                $query->with($relation);
            }
        }
        return $query;
    }

    /**
     * 规范化排序条件（字段必须在白名单内，否则回退默认排序）
     *
     * @param array<string,string> $defaultOrder 默认排序，如 ['id' => 'desc']
     */
    public static function normalizeOrder(mixed $orderField, mixed $orderBy, array $allowedFields, array $defaultOrder): array
    {
        $direction = is_string($orderBy) && strtolower($orderBy) === 'asc' ? 'asc' : 'desc';
        $field = is_string($orderField) ? $orderField : '';
        if ($field !== '' && in_array($field, $allowedFields, true)) {
            return [$field => $direction];
        }
        return $defaultOrder !== [] ? $defaultOrder : ['id' => 'desc'];
    }

    /**
     * 从列表列配置中收集允许排序的字段
     *
     * @param array $tables tables 配置，含 fields 与 default_order
     * @return array<int,string>
     */
    public static function collectSortableFields(array $tables, array $defaultOrder): array
    {
        $fields = [];
        foreach ($tables['fields'] ?? [] as $column) {
            if (is_array($column) && !empty($column['sort']) && isset($column['field']) && is_string($column['field'])) {
                $fields[] = $column['field'];
            }
        }
        // 默认排序字段即便未显式标记 sort 也必须允许，否则首次加载即被重置
        foreach (array_keys($defaultOrder) as $field) {
            if (is_string($field)) {
                $fields[] = $field;
            }
        }
        $fields[] = 'id';
        return array_values(array_unique($fields));
    }

    /**
     * 规范化分页参数
     *
     * @return array{0:int,1:int} [page, limit]
     */
    public static function normalizePagination(array $params, bool $isPage, int $maxLimit, mixed $fallbackLimit = null): array
    {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $page = $page > 0 ? $page : 1;

        if (!$isPage) {
            // 关闭分页时使用上限内的最大页容量，保持既有行为（200）
            $limit = min($maxLimit, self::DEFAULT_MAX_LIMIT);
        } else {
            $limit = $fallbackLimit !== null ? (int)$fallbackLimit : 20;
            if (isset($params['limit']) && is_numeric($params['limit'])) {
                $limit = (int)$params['limit'];
            }
            $limit = $limit > 0 ? $limit : 20;
            $limit = min($limit, $maxLimit);
        }
        return [$page, $limit];
    }

    /**
     * 提取需要回填到搜索表单的查询参数
     */
    public static function extractWhere(array $search, array $requestData): array
    {
        $where = [];
        foreach ($search as $config) {
            if (!is_string($config)) {
                continue;
            }
            $parsed = self::parseSearchConfig($config);
            if ($parsed === null) {
                continue;
            }
            [$fields, , $paramKey] = $parsed;
            if (!array_key_exists($paramKey, $requestData)) {
                continue;
            }
            $value = $requestData[$paramKey];
            // 仅回填标量，数组值交给表单组件自行处理
            if (is_scalar($value)) {
                $where[$paramKey] = $value;
            }
            unset($fields);
        }
        return $where;
    }

    /**
     * 推导快捷编辑允许更新的字段白名单
     *
     * 来源：表单字段 name + 列表中声明 edit 类型为 text/textarea 的列 + switch 列 + 显式补充
     */
    public static function collectQuickEditFields(array $forms, array $tables, array $extra = []): array
    {
        $fields = [];
        foreach ($forms['fields'] ?? [] as $field) {
            if (is_array($field) && isset($field['name']) && is_string($field['name'])) {
                $fields[] = $field['name'];
            }
        }
        foreach ($tables['fields'] ?? [] as $column) {
            if (!is_array($column) || !isset($column['field']) || !is_string($column['field'])) {
                continue;
            }
            $isEditable = isset($column['edit']) && in_array($column['edit'], ['text', 'textarea'], true);
            $isSwitch = ($column['templet'] ?? '') === 'switch';
            if ($isEditable || $isSwitch) {
                $fields[] = $column['field'];
            }
        }
        foreach ($extra as $field) {
            if (is_string($field) && $field !== '') {
                $fields[] = $field;
            }
        }
        return array_values(array_unique($fields));
    }
}
