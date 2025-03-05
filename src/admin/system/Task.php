<?php

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemTasks;
use Cron\CronExpression;
use think\App;

class Task extends BaseAdmin
{
    use CrudTrait;

    /**
     * 页面标题
     * @var string
     */
    protected string $title = '计划任务';
    /**
     * 默认排序
     * @var array
     */
    protected array $default_order = [
        'list' => 'desc'
    ];

    public function __construct(SystemTasks $model, App $app)
    {
        parent::__construct($app);
        $this->model = $model;
        $this->setParams();
    }

    /**
     * @return void
     */
    protected function setParams(): void
    {
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
                    'title' => '任务名称',
                    'align' => 'content'
                ], [
                    'field' => 'exptime',
                    'title' => 'cron表达式',
                ], [
                    'field' => 'exptime_info',
                    'title' => '运行周期',
                ], [
                    'field' => 'task',
                    'title' => '任务类/命令',
                    'edit' => 'text',
                ], [
                    'field' => 'last_time',
                    'title' => '最后执行',
                ], [
                    'field' => 'next_time',
                    'title' => '下次执行',
                ],
                [
                    'field' => 'list',
                    'title' => '排序',
                    'edit' => 'text',
                    'sort' => true,
                ], [
                    'field' => 'status',
                    'title' => '状态',
                    'templet' => 'switch'
                ],
            ],
            'top_button' => [

            ],
            'right_button' => [

            ]

        ];

        $this->forms = [
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'title',
                    'label' => '菜单名称',
                    'required' => true
                ], [
                    'type' => 'cron',
                    'name' => 'exptime',
                    'label' => 'cron 表达式',
                    'options' => '',
                    'required' => true,
                    'help' => 'cron表达式，不支持 秒级执行'
                ], [
                    'type' => 'text',
                    'name' => 'task',
                    'label' => '任务类/命令',
                    'required' => true
                ], [
                    'type' => 'textarea',
                    'name' => 'data',
                    'label' => '任务参数',
                ]
            ]
        ];
        $this->search = [
            'id#=#id', 'title#like#name', 'task#=#task', 'status#=#status', 'last_time#between#last_time', 'next_time#between#next_time', 'create_time#between#create_time', 'update_time#between#update_time'
        ];
    }

    /**
     * 处理列表数据
     * @param $data
     * @return void
     */
    protected function _index_list_filter(&$data): void
    {
        foreach ($data['data'] as &$vo) {
            $vo['exptime_info'] = (new \mowzs\lib\helper\CronExpressionParserHelper)->parse($vo['exptime']);
        }
    }

    /**
     * cron表达式生成
     * @return void
     * @throws \Exception
     */
    public function cron(): void
    {
        // 获取前端传递的 Cron 表达式
        $cron = $this->request->param('cron');

        // 检查 Cron 表达式是否为空
        if (empty($cron)) {
            $this->error('Cron 表达式不能为空');
        }

        // 分割 Cron 表达式为数组
        $cronParts = explode(' ', $cron);

        // 如果是 6 字段格式，忽略第一个字段（秒）
        if (count($cronParts) === 6) {
            array_shift($cronParts); // 移除秒字段
        }

        // 重新组合 Cron 表达式
        $cron = implode(' ', $cronParts);

        // 创建 Cron 表达式对象（使用 createFromFormat 替代 factory）
        $expression = new CronExpression($cron);

        // 获取当前时间
        $now = new \DateTime();

        // 获取未来 10 次任务时间
        $nextRuns = [];
        for ($i = 0; $i < 10; $i++) {
            $nextRuns[] = $expression->getNextRunDate($now, $i, true)->format('Y-m-d H:i:s');
        }

        $this->success('运行结果', $nextRuns);

    }
}
