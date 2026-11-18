<?php
declare (strict_types=1);

namespace app\common\traits;

use think\Model;
use think\db\Query;
use think\Exception;
use happy\admin\libs\Forms;
use app\common\util\CrudUtil;
use app\common\util\CrudQuery;
use think\db\exception\DbException;
use think\model\contract\Modelable;
use think\db\exception\DataNotFoundException;
use happy\admin\libs\Exception\FormsException;
use think\db\exception\ModelNotFoundException;
use think\template\exception\TemplateNotFoundException;

trait CrudTrait
{
    /**
     * 用于存储模块名称
     * @var string
     */
    protected string $moduleName;
    /**
     * 模型
     * @var Model
     */
    protected Model $model;
    /**
     * 搜索
     * @var array
     */
    protected array $search = [];
    /**
     * 列表数据
     * @var array
     */
    protected array $tables = [];
    /**
     * 表单数据
     * @var array|array[]
     */
    protected array $forms = ['fields' => []];
    /**
     * 开启分页
     * @var bool
     */
    protected bool $is_page = true;
    /**
     * 分页容量上限，防止超大 limit 拖库
     * @var int
     */
    protected int $maxLimit = CrudQuery::DEFAULT_MAX_LIMIT;
    /**
     * 允许前端通过 with 参数预加载的关联名白名单。
     * 默认为空（禁止所有外部指定关联），需要时由子类 setParams() 声明。
     * @var array<int,string>
     */
    protected array $allowedRelations = [];
    /**
     * 快捷编辑允许更新的字段白名单，留空时从 forms/tables 自动推导。
     * @var array<int,string>
     */
    protected array $quickEditFields = [];

    /**
     * 内容列表
     * @auth true
     * @return string
     * @throws DbException
     * @throws Exception
     */
    public function index(): string
    {
        $params = $this->request->param();

        // 返回数据表格数据
        if ($this->isLayTable()) {
            // 构建查询（含搜索条件安全转义与 OR 分组）
            $query = $this->buildWhereConditions($this->model, $params);
            // 关联预加载：仅允许白名单内的关联
            $query = CrudQuery::applyWith($query, $params['with'] ?? null, $this->allowedRelations);

            // 设置排序（字段白名单校验）
            $query = $this->setListOrder($query, $params);

            // 分页参数统一收敛到上限内
            [$page, $limit] = CrudQuery::normalizePagination(
                $params,
                $this->is_page,
                $this->maxLimit,
                $this->limit ?? 20
            );
            $paginateResult = $query->paginate([
                'page' => $page,
                'list_rows' => $limit,
            ]);

            $data = $paginateResult->toArray();
            $this->callback('_list_filter', $data);
            $this->success($data);
            // success() 抛出 HttpResponseException 中断；显式 return 避免控制流依赖异常
            return '';
        }

        if (!empty($this->search)) {
            $this->assign([
                'search_code' => Forms::instance(['display' => false, 'outputMode' => 'code'])
                    ->setFormHtml([
                        'data-table-id' => get_lay_table_id(),
                    ])
                    ->setSubmit('搜索')
                    ->render($this->getSearchFields(), 'form_search'),
            ]);
        }

        // 分配模板变量
        $this->assign([
            'where' => $this->bulidWhere(),
            'right_button' => CrudUtil::getButtonHtml($this->tables['right_button'] ?? []),
            'top_button' => CrudUtil::getButtonHtml($this->tables['top_button'] ?? [], 'top'),
        ]);

        // 渲染页面
        try {
            return $this->fetch();
        } catch (TemplateNotFoundException $exception) {
            // 模板不存在时尝试读取公用模板
            return $this->fetch('common@/page_table');
        }
    }

    /**
     * 判断是否为数据表格请求
     */
    protected function isLayTable(): bool
    {
        return $this->request->isAjax() && ($this->request->param('out') == 'json' || $this->request->header('out') == 'json');
    }

    /**
     * 构建 where 查询条件
     *
     * 保留方法名与签名供 ContentAdmin / Nav 重写；内部委托 CrudQuery 做安全处理。
     *
     * @param Model|Query $model
     * @param array $requestData
     * @return Query|Model
     */
    protected function buildWhereConditions(Model|Query $model, $requestData): Model|Query
    {
        return CrudQuery::applySearch($model, $this->search, $requestData);
    }

    /**
     * 设置列表的排序规则（字段白名单校验）
     */
    protected function setListOrder(Query|Model $query, array $params): Model|Query
    {
        $defaultOrder = $this->default_order ?? ['id' => 'desc'];
        $allowedFields = CrudQuery::collectSortableFields($this->tables, $defaultOrder);
        $order = CrudQuery::normalizeOrder(
            $params['_order'] ?? '',
            $params['_by'] ?? '',
            $allowedFields,
            $defaultOrder
        );
        return $query->order($order);
    }

    /**
     * 获取搜索字段
     * @return array
     */
    protected function getSearchFields(): array
    {
        $searchFields = [];
        $formFields = $this->forms['fields'] ?? [];
        $searchConfig = $this->search;

        // 创建一个映射，用于快速查找表单字段
        $fieldMap = array_column($formFields, null, 'name');

        // 动态添加缺失的字段，并存储在一个单独的数组中
        $dynamicFields = [];

        // 动态添加 status 字段
        if (!isset($fieldMap['status'])) {
            $dynamicFields['status'] = [
                'type' => 'select',
                'name' => 'status',
                'label' => '状态',
                'options' => [1 => '正常', 0 => '待审'],
            ];
        }
        if (!isset($fieldMap['id'])) {
            $dynamicFields['id'] = [
                'type' => 'text',
                'name' => 'id',
                'label' => 'ID',
            ];
        }

        // 动态添加 create_time 字段
        if (!isset($fieldMap['create_time'])) {
            $dynamicFields['create_time'] = [
                'type' => 'daterange',
                'name' => 'create_time',
                'label' => '创建时间',
            ];
        }

        // 动态添加 update_time 字段
        if (!isset($fieldMap['update_time'])) {
            $dynamicFields['update_time'] = [
                'type' => 'daterange',
                'name' => 'update_time',
                'label' => '更新时间',
            ];
        }

        // 合并动态字段到 fieldMap 中
        $fieldMap = array_merge($fieldMap, $dynamicFields);
        foreach ($searchConfig as $config) {
            $parsed = CrudQuery::parseSearchConfig((string)$config);
            if ($parsed === null) {
                continue;
            }
            [$fields, , $paramKey] = $parsed;

            // 如果是多个字段使用 | 分隔符进行分割
            $fieldList = explode('|', $fields);
            foreach ($fieldList as $field) {
                $field = trim($field);
                if (isset($fieldMap[$field])) {
                    // 复制表单字段并移除 required 属性
                    $searchField = $fieldMap[$field];
                    $searchField['name'] = $paramKey;
                    if (in_array($searchField['type'], ['radio', 'checkbox'])) {
                        $searchField['type'] = 'select';
                    }
                    if (in_array($searchField['type'], ['hidden']) && isset($searchField['options']) && is_array($searchField['options'])) {
                        $searchField['type'] = 'select';
                    }
                    unset($searchField['required']);
                    if (in_array($searchField['type'], ['datetime', 'date'])) {
                        $searchField['type'] = 'daterange';
                    }
                    // 添加到搜索字段列表
                    $searchFields[] = $searchField;
                }
            }
        }
        return $searchFields;
    }

    /**
     * 绑定 get（保留历史方法名以兼容 Nav 等重写）
     * @return array
     */
    protected function bulidWhere(): array
    {
        return CrudQuery::extractWhere($this->search, $this->request->param());
    }

    /**
     * 添加
     * @auth true
     * @return bool|string
     * @throws Exception
     */
    public function add(): bool|string
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (false === $this->callback('_save_filter', $data)) {
                return false;
            }
            try {
                $this->checkRequiredFields($data);
                if ($this->model->save($data)) {
                    $model = $this->model->getModel();
                    // 结果回调处理（保持三参数契约）
                    $result = true;
                    if (false === $this->callback('_save_result', $result, $model, $data)) {
                        return $result;
                    }
                    $this->success('添加成功');
                } else {
                    $this->error('添加失败');
                }
            } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
                $this->error('添加失败：' . $e->getMessage());
            }
        }
        if (empty($this->forms['fields'])) {
            $this->error('未设置 forms 参数');
        }
        $forms = Forms::instance();
        if (!empty($this->forms['trigger'])) {
            $forms = $forms->setTriggers($this->forms['trigger']);
        }
        if (!empty($this->forms['pk'])) {
            $forms = $forms->setPk($this->forms['pk']);
        }
        return $forms->render($this->forms['fields']);
    }

    /**
     * 校验必填项不能为空
     * @param array $data
     * @return void
     */
    protected function checkRequiredFields(array $data): void
    {
        // 获取必填字段
        $required = CrudUtil::getRequiredFieldNames($this->forms['fields']);
        // 检查是否获取到了数据
        if (!empty($required)) {
            // 遍历必填字段
            foreach ($required as $field => $label) {
                if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                    $this->error($label . '不能为空');
                }
            }
        }
    }

    /**
     * 修改
     * @auth true
     * @param int $id
     * @return bool|null
     * @throws Exception
     * @throws FormsException
     */
    public function edit(int $id = 0): ?bool
    {
        try {
            $data = $this->request->post();
            $record = $this->model->findOrEmpty($id);

            if ($record->isEmpty()) {
                $this->error('记录不存在');
            }
            if ($this->request->isGet()) {
                if (empty($this->forms['fields'])) {
                    $this->error('未设置 forms 参数');
                }
                $recordData = $record->toArray();
                $forms = Forms::instance()->setValue($recordData);
                if (!empty($this->forms['trigger'])) {
                    $forms = $forms->setTriggers($this->forms['trigger']);
                }
                if (!empty($this->forms['pk'])) {
                    $forms = $forms->setPk($this->forms['pk']);
                }
                // render 在 page 模式抛出 HttpResponseException 中断
                $forms->render($this->forms['fields']);
                return true;
            }
            if (false === $this->callback('_save_filter', $data, $record)) {
                return false;
            }
            $this->checkRequiredFields($data);
            // 保存前获取原始数据的副本
            $originalData = $record->toArray();
            $record->save($data);
            // 结果回调处理（保持三参数契约：第二参数为编辑前原始数据）
            $result = true;
            if (false === $this->callback('_save_result', $result, $originalData, $data)) {
                return $result;
            }
            $this->success('更新成功');
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            $this->error('记录不存在：' . $e->getMessage());
        }
        return null;
    }

    /**
     * 删除
     * @auth true
     * @return bool|mixed|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            $this->error('请求错误');
        }

        $ids = $this->request->param('ids');
        if ($ids === null || $ids === '' || $ids === []) {
            $this->error('id不能为空');
        }

        // 统一单条与批量：ids 归一为数组，回调第二参数保持原始类型（兼容既有钩子）
        $isBatch = is_array($ids);
        $idList = $isBatch ? array_values(array_filter($ids, static fn($v) => is_scalar($v) && $v !== '')) : [$ids];
        if ($idList === []) {
            $this->error('id不能为空');
        }

        $records = $this->model->whereIn('id', $idList)->select();
        if ($records->isEmpty()) {
            $this->error('记录不存在');
        }

        // 关键修复：批量分支原本引用未定义的 $record / $result，导致拦截回调失效
        $firstRecord = $records->first();
        if (false === $this->callback('_delete_filter', $firstRecord, $ids)) {
            return false;
        }

        $deleted = 0;
        foreach ($records as $record) {
            if ($record->delete()) {
                $deleted++;
            }
        }
        $result = $deleted > 0;

        if (false === $this->callback('_delete_result', $result, $ids)) {
            return $result;
        }
        $this->success('删除成功');
    }

    /**
     * 列表快捷编辑（单元格编辑与开关共用）
     * @auth true
     * @param mixed $id
     * @return false|Modelable|void
     */
    public function quickEdit($id)
    {
        if (empty($id)) {
            $this->error('id不能为空');
        }
        $field = $this->request->post('field');
        $value = $this->request->post('value');
        if (!is_string($field) || $field === '') {
            $this->error('字段名不合法');
        }

        // 关键修复：字段白名单校验，杜绝任意字段越权写入
        $allowedFields = $this->quickEditFields !== []
            ? $this->quickEditFields
            : CrudQuery::collectQuickEditFields($this->forms, $this->tables);
        if ($allowedFields !== [] && !in_array($field, $allowedFields, true)) {
            $this->error('该字段不允许快捷编辑');
        }

        if (false === $this->callback('_quick_edit_filter', $field, $value)) {
            return false;
        }
        $result = $this->model->update(['id' => $id, $field => $value]);
        if (false === $this->callback('_quick_edit_result', $result, $id)) {
            return $result;
        }
        $this->success('更新成功');
    }

    /**
     * 合并数据 name索引
     * @param array $existing_fields
     * @param array $new_fields
     * @param string $key
     * @return array
     */
    protected function mergeFields(array $existing_fields, array $new_fields, $key = 'name'): array
    {
        // 创建一个临时数组来存储字段名称和对应的索引
        $field_map = [];
        foreach ($existing_fields as $index => $field) {
            $field_map[$field[$key]] = $index;
        }

        // 遍历 new_fields 并更新或添加字段
        foreach ($new_fields as $new_field) {
            if (isset($field_map[$new_field[$key]])) {
                // 如果字段已存在，则覆盖
                $index = $field_map[$new_field[$key]];
                $existing_fields[$index] = array_merge($existing_fields[$index], $new_field);
            } else {
                // 如果字段不存在，则添加
                $existing_fields[] = $new_field;
            }
        }

        return $existing_fields;
    }

    /**
     * 获取模块名称。
     * @return string 模块名称
     */
    protected function getModuleName(): string
    {
        return $this->request->layer(true);
    }

    /**
     * 设置参数（由子类覆写以配置 model/tables/forms/search）
     * @return void
     */
    protected function setParams()
    {
    }
}
