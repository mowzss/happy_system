<?php

declare(strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\common\util\CrudUtil;
use app\model\system\SystemNav;
use mowzs\lib\Forms;
use mowzs\lib\helper\DataHelper;
use think\App;

/**
 * 网站导航管理
 */
class Nav extends BaseAdmin
{
    use CrudTrait;

    /**
     * 页面标题
     * @var string
     */
    protected string $title = '网站导航菜单';

    /**
     * 默认排序
     * @var array
     */
    protected array $default_order = [
        'list' => 'desc'
    ];

    /**
     * 开启树形表格
     * @var bool
     */
    protected bool $is_tree = true;
    /**
     * @var array
     */
    protected array $menuType;
    protected array $where;

    /**
     * @param App $app
     * @param SystemNav $systemSiteMenu
     */
    public function __construct(App $app, SystemNav $systemSiteMenu)
    {
        parent::__construct($app);
        $this->model = $systemSiteMenu;
        $this->is_page = false;
        $this->app->config->load('extra/nav', 'nav'); // 加载反馈配置
        $this->menuType = (array)$app->config->get('nav');
        $this->setParams();
    }

    public function index(): string
    {
        $params = $this->request->param();
        $this->where = ['dir' => $this->request->param('dir', 'pc')];
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
            'dirs' => $this->menuType
        ]);
        //渲染页面
        return $this->fetch();
    }

    /**
     * 设置表格字段、表单字段和搜索条件
     * @return void
     */
    protected function setParams(): void
    {
        // 定义表格字段
        $this->tables = [
            'fields' => [
                [
                    'field' => 'id',
                    'title' => 'ID',
                    'width' => 80,
                    'sort' => true,
                ],
                [
                    'field' => 'title',
                    'title' => '菜单名称',
                    'align' => 'left'
                ],
                [
                    'field' => 'dir_name',
                    'title' => '所属分类',
                    'width' => 120,
                ],
                [
                    'field' => 'url',
                    'title' => '链接地址',
                    'width' => 300,
                    'edit' => 'text',
                ], [
                    'field' => 'list',
                    'title' => '排序',
                    'edit' => 'text',
                    'sort' => true,
                    'width' => 80,
                ],
                [
                    'field' => 'target',
                    'title' => '打开方式',
                    'edit' => 'select',
                    'options' => ['_self' => '_self', '_blank' => '_blank'],
                ],
                [
                    'field' => 'status',
                    'title' => '状态',
                    'templet' => 'switch',
                    'width' => 100,
                ],

            ],
            'tips' => '网站导航分类 需在项目配置文件/config/extra/nav.php中进行设置'
        ];

        // 定义表单字段
        $this->forms = [
            'fields' => [
                [
                    'type' => 'hidden',
                    'name' => 'dir',
                    'label' => '分类',
                    'options' => $this->menuType,
                    'help' => '分类数据可以在项目配置目录下extra/menu.php文件中增加分类配置',
                    'required' => true,
                    'value' => $this->request->param('dir')
                ],
                [
                    'type' => 'select',
                    'name' => 'pid',
                    'label' => '父级栏目',
                    'options' => $this->model->getMenuForm(['status' => 1, 'dir' => $this->request->param('dir')]),
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'title',
                    'label' => '菜单名称',
                    'required' => true,
                ],
                [
                    'type' => 'icon',
                    'name' => 'icon',
                    'label' => '图标',
                ],
                [
                    'type' => 'radio',
                    'name' => 'type',
                    'label' => '类型',
                    'options' => ["1" => '节点', "2" => '链接'],
                    'default' => "1",
                ], [
                    'type' => 'text',
                    'name' => 'node',
                    'label' => '节点',
                    'help' => '请输入节点标识（如：admin/system/index）',
                ], [
                    'type' => 'text',
                    'name' => 'url',
                    'label' => 'url',
                    'help' => '请输入链接地址',
                ],
                [
                    'type' => 'textarea',
                    'name' => 'params',
                    'label' => '参数',
                    'help' => 'url ?后的参数',
                ],

                [
                    'type' => 'select',
                    'name' => 'target',
                    'label' => '打开方式',
                    'options' => ['_self' => '_self', '_blank' => '_blank'],
                    'default' => '_self',
                ],
                [
                    'type' => 'text',
                    'name' => 'class',
                    'label' => '自定义样式类',
                    'help' => '请输入CSS类名，多个 空格分割',
                ],
                [
                    'type' => 'text',
                    'name' => 'list',
                    'label' => '排序',
                    'default' => 0,
                ]
            ],
            'trigger' => [
                [
                    'name' => 'type',
                    'values' => [
                        ['value' => "1", 'field' => ['node', 'params']],
                        ['value' => "2", 'field' => ['url']],
                    ]
                ],
            ]
        ];

        // 定义搜索条件
        $this->search = [
            'id#=#id',
            'title#like#title',
            'dir#=#dir',
            'url#like#url',
            'node#=#node',
            'status#=#status',
        ];
    }

    /**
     * 处理列表数据，构建树形结构
     * @param array $data
     * @return void
     */
    protected function _index_list_filter(array &$data): void
    {
        // 获取配置中的 dir 数据
        $menuDirs = $this->menuType;

        // 确保 data['data'] 存在并且是一个数组
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as &$v) {
                // 根据 dir 设置 dir_name
                if (isset($v['dir']) && isset($menuDirs[$v['dir']])) {
                    $v['dir_name'] = $menuDirs[$v['dir']];
                } else {
                    $v['dir_name'] = '未知分类'; // 默认值，当 dir 未找到时
                }
            }
            unset($v); // 解除引用
        }

        // 构建树形结构
        $data['data'] = DataHelper::instance()->arrToTree($data['data']);
    }

    /**
     * 保存前数据处理
     * @param $data
     * @return void
     */
    protected function _save_filter(&$data): void
    {
        if (!empty($data['node'])) {
            $data['url'] = hurl($data['node'], $data['params'] ?: []);
        }
    }
}
