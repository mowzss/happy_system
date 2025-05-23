<?php
declare (strict_types=1);

namespace app\common\traits;

use app\common\util\CrudUtil;
use mowzs\lib\Forms;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\Exception;
use think\Model;
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
    protected array $tables;
    /**
     * 表单数据
     * @var array|array[]
     */
    protected array $forms;
    /**
     * 开启分页
     * @var
     */
    protected $is_page = true;


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
        //  返回数据表格数据
        if ($this->isLayTable()) {
            // 构建查询
            $query = $this->buildWhereConditions($this->model, $params);
            // 处理关联查询
            if (isset($params['with'])) {
                foreach (explode(',', $params['with']) as $relation) {
                    $query->with(trim($relation));
                }
            }

            //设置排序
            $query = $this->setListOrder($query, $params);
            // 分页
            $page = $params['page'] ?? 1;
            $limit = $params['limit'] ?? ($this->limit ?? 20);
            if (!$this->is_page) {
                $limit = 200;
            }
            $paginateResult = $query->paginate([
                'page' => $page,
                'list_rows' => $limit
            ]);


            // 转换结果为数组
            $data = $paginateResult->toArray();

            // 回调过滤器
            $this->callback('_list_filter', $data);
            $this->success($data);
        }
        if (!empty($this->search)) {
            $this->assign([
                'search_code' => Forms::instance(['display' => false, 'outputMode' => 'code'])
                    ->setFormHtml([
                        'data-table-id' => get_lay_table_id()
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
        //渲染页面
        try {
            return $this->fetch();
        } catch (TemplateNotFoundException $exception) {
            //模板不存在时 尝试读取公用模板
            return $this->fetch('common@/page_table');
        }

    }

    /**
     * 绑定get
     * @return array
     */
    protected function bulidWhere(): array
    {
        $get = $this->request->param();
        $where = [];
        foreach ($this->search as $config) {
            // 拆分配置字符串
            list($fields, $operator, $paramKey) = explode('#', $config);
            if (isset($get[$paramKey])) {
                $where[$paramKey] = $get[$paramKey];
            }
        }
        return $where;
    }

    /**
     * 构建where查询条件
     * @param Model|Query $model
     * @param $requestData
     * @return Query|Model
     */
    protected function buildWhereConditions(Model|Query $model, $requestData): Model|Query
    {
        // 使用类的 search 属性作为搜索配置
        foreach ($this->search as $config) {
            // 拆分配置字符串
            list($fields, $operator, $paramKey) = explode('#', $config);

            // 如果是多个字段使用 | 分隔符进行分割
            $fieldList = explode('|', $fields);
            // 检查请求数据中是否存在对应的参数
            if (isset($requestData[$paramKey])) {
                $value = $requestData[$paramKey];
                // 跳过空值
                if (empty($value)) {
                    continue;
                }

                // 对于 like 操作符，防止 SQL 注入攻击，处理用户输入
                if ($operator == 'like') {
                    $value = '%' . addcslashes($value, '_%') . '%';
                }
                if ($operator == 'between') {
                    $operator = 'BETWEEN TIME';
                    $value = explode(' - ', $value);
                }
                // 构建条件
                if (count($fieldList) > 1) {
                    // 多个字段用 OR 连接
                    $orConditions = [];
                    foreach ($fieldList as $field) {
                        $orConditions[] = [$field, $operator, $value];
                    }
                    // 使用模型的 whereOr 方法来构建 or 查询
                    foreach ($orConditions as $condition) {
                        $model = $model->whereOr(...$condition);
                    }
                } else {
                    // 单个字段直接添加条件
                    $model = $model->where($fields, $operator, $value);
                }
            }
        }
        // 返回修改后的模型实例
        return $model;
    }

    /**
     * 添加
     * @auth true
     * @return bool|string
     * @throws Exception
     */
    public function add()
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
                    // 结果回调处理
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
     * @param $data
     * @return void
     */
    protected function checkRequiredFields($data)
    {
        // 获取必填字段
        $required = CrudUtil::getRequiredFieldNames($this->forms['fields']);
        // 检查是否获取到了数据
        if (!empty($required)) {
            // 遍历必填字段
            foreach ($required as $field => $label) {
                if (!isset($data[$field]) || $data[$field] == '') {
                    $this->error($label . '不能为空');
                }
            }
        }
    }

    /**
     * 修改
     * @auth true
     * @param string $id
     * @return bool|void
     * @throws Exception
     */
    public function edit(string $id = '')
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
                $forms = Forms::instance()->setValue($record->toArray());
                if (!empty($this->forms['trigger'])) {
                    $forms = $forms->setTriggers($this->forms['trigger']);
                }
                if (!empty($this->forms['pk'])) {
                    $forms = $forms->setPk($this->forms['pk']);
                }
                $forms->render($this->forms['fields']);
            }
            if (false === $this->callback('_save_filter', $data, $record)) {
                return false;
            }
            $this->checkRequiredFields($data);
            $record->save($data);
            // 结果回调处理
            $result = true;
            if (false === $this->callback('_save_result', $result, $record, $data)) {
                return $result;
            }
            $this->success('更新成功');
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            $this->error('记录不存在：' . $e->getMessage());
        }
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
        if ($this->request->isPost()) {
            $ids = $this->request->param('ids');

            if (is_null($ids)) {
                $this->error('id不能为空');
            }
            if (is_array($ids)) {
                // 批量删除
                $records = $this->model->whereIn('id', $ids)->select();

                if (!$records->isEmpty()) {
                    if (false === $this->callback('_delete_filter', $record, $ids)) {
                        return false;
                    }
                    $records->each(function ($record) {
                        return $record->delete();
                    });
                    if (false === $this->callback('_delete_result', $result, $ids)) {
                        return $result;
                    }
                    $this->success('删除成功');
                } else {
                    $this->error('记录不存在');
                }
            } else {
                // 单个删除
                $record = $this->model->findOrEmpty($ids);
                if (!$record->isEmpty()) {
                    if (false === $this->callback('_delete_filter', $record, $ids)) {
                        return false;
                    }
                    $result = $record->delete();
                    if (false === $this->callback('_delete_result', $result, $ids)) {
                        return $result;
                    }
                    $this->success('删除成功');
                } else {
                    $this->error('记录不存在');
                }
            }
        } else {
            $this->error('请求错误');
        }

    }

    /**
     * 列表编辑
     * @auth true
     * @param $id
     * @return void
     */
    public function quickEdit($id): void
    {
        if (empty($id)) {
            $this->error('id不能为空');
        }
        $field = $this->request->post('field');
        $value = $this->request->post('value');

        $this->model->update(['id' => $id, $field => $value]);
        $this->success('更新成功');
    }

    /**
     * @return bool
     */
    protected function isLayTable(): bool
    {
        return $this->request->isAjax() && ($this->request->param('out') == 'json' || $this->request->header('out') == 'json');
    }

    /**
     * 设置列表的排序规则
     *
     * @param Query|Model $query 查询构建器对象
     * @param array $params 请求参数
     * @return Query|Model
     */
    protected function setListOrder(Query|Model $query, array $params): Model|Query
    {
        // 获取排序参数
        $orderField = $params['_order'] ?? 'id'; // 默认按 list 排序
        $orderBy = strtolower($params['_by'] ?? '') === 'asc' ? 'asc' : 'desc'; // 默认降序

        // 设置默认排序
        $defaultOrder = $this->default_order ?? ['id' => 'desc'];

        // 如果提供了自定义排序，则覆盖默认排序
        if (!empty($orderField)) {
            $defaultOrder = [$orderField => $orderBy];
        }
        // 应用排序
        return $query->order($defaultOrder);
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
     * 获取搜索字段
     * @return array
     */
    protected function getSearchFields(): array
    {
        $searchFields = [];
        $formFields = $this->forms['fields'];
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
            // 拆分配置字符串
            list($fields, $operator, $paramKey) = explode('#', $config);

            // 如果是多个字段使用 | 分隔符进行分割
            $fieldList = explode('|', $fields);
            foreach ($fieldList as $field) {
                if (isset($fieldMap[$field])) {
                    // 复制表单字段并移除 required 属性
                    $searchField = $fieldMap[$field];
                    $searchField['name'] = $paramKey;
                    if (in_array($searchField['type'], ['radio', 'checkbox'])) {
                        $searchField['type'] = 'select';
                    }
                    if (in_array($searchField['type'], ['hidden']) && is_array($searchField['options'])) {
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
     * 获取模块名称。
     * @return string 模块名称
     */
    protected function getModuleName(): string
    {
        return $this->request->layer(true);
    }

    /**
     * 设置参数
     * @return void
     */
    protected function setParams()
    {

    }
}
