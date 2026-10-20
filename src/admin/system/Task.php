<?php

declare (strict_types=1);

namespace app\admin\system;

use think\App;
use Cron\CronExpression;
use think\facade\Console;
use app\common\traits\CrudTrait;
use app\model\system\SystemTasks;
use app\common\controllers\BaseAdmin;
use happy\admin\libs\task\Task as Tasks;
use happy\admin\libs\Exception\LibsException;

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
        'list' => 'desc',
    ];
    
    /**
     * @param SystemTasks $model
     * @param App $app
     */
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
            'title' => $this->title,
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
                    'edit' => 'text',
                    'width' => 140,
                ], [
                    'field' => 'exptime',
                    'title' => 'cron表达式',
                    'width' => 140,
                ], [
                    'field' => 'exptime_info',
                    'title' => '运行周期',
                    'width' => 140,
                ], [
                    'field' => 'task',
                    'title' => '任务类/命令',
                    'edit' => 'text',
                ], [
                    'field' => 'last_time',
                    'title' => '最后执行', 'width' => 160,
                ], [
                    'field' => 'next_time',
                    'title' => '下次执行', 'width' => 160,
                ],
                [
                    'field' => 'count',
                    'title' => '执行次数',
                    'width' => 120,
                    'sort' => true,
                ],
                [
                    'field' => 'list',
                    'title' => '排序',
                    'edit' => 'text',
                    'sort' => true,
                ], [
                    'field' => 'status',
                    'title' => '状态',
                    'templet' => 'switch',
                ],
            ],
            'top_button' => [
            
            ],
            'right_button' => [
                //['event' => '', 'type' => 'data-open', 'name' => '执行', 'icon' => 'layui-icon-play', 'class' => 'layui-btn-xs layui-btn-normal'],
                ['event' => 'edit'],
                ['event' => 'del'],
            ],
        
        ];
        
        $this->forms = [
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'title',
                    'label' => '菜单名称',
                    'required' => true,
                ], [
                    'type' => 'cron',
                    'name' => 'exptime',
                    'label' => 'cron 表达式',
                    
                    'options' => '',
                    'required' => true,
                    'help' => 'cron表达式，不支持 秒级执行',
                ], [
                    'type' => 'text',
                    'name' => 'task',
                    'label' => '任务类/命令',
                    'required' => true,
                ], [
                    'type' => 'textarea',
                    'name' => 'data',
                    'label' => '任务参数',
                ],
                [
                    'type' => 'datetime',
                    'name' => 'next_time',
                    'label' => '下次执行时间',
                    'value' => date('Y-m-d H:i:s'),
                ],
                [
                    'type' => 'textarea',
                    'name' => 'output_msg',
                    'label' => '输出信息',
                    'disabled' => true,
                    'value' => '新增状态或当前任务没有执行记录的输出信息',
                ],
            ],
        ];
        $this->search = [
            'id#=#id', 'title#like#name', 'task#=#task', 'status#=#status', 'last_time#between#last_time', 'next_time#between#next_time', 'create_time#between#create_time', 'update_time#between#update_time',
        ];
    }
    
    /**
     * 手动执行任务
     * @return void
     */
    public function execute(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('任务ID不能为空');
        }
        
        $taskInfo = $this->model->findOrEmpty($id);
        if ($taskInfo->isEmpty()) {
            $this->error('任务不存在');
        }
        $taskInfo = $taskInfo->toArray();
        
        try {
            $taskClass = $taskInfo['task'];
            
            // 判断是任务类还是命令
            if (class_exists($taskClass) && is_subclass_of($taskClass, Tasks::class)) {
                /** @var Tasks $task */
                $task = $this->app->invokeClass($taskClass, [$taskInfo]);
                $result = $task->run('manual');
            } else {
                // 命令行任务
                $command = explode(' ', $taskClass);
                $outputContent = Console::call($command[0], array_slice($command, 1))->fetch();
                $result = [
                    'status' => 'success',
                    'message' => '命令执行完成',
                    'output' => $outputContent,
                    'cost_time' => 0,
                ];
            }
            
            // 更新输出信息到数据库
            if (!empty($result['output'])) {
                // 截取前 1000 字符，避免过长
                $outputMsg = mb_substr($result['output'], 0, 1000);
                $this->model->where('id', $id)->update(['output_msg' => $outputMsg]);
            }
            
            if ($result['status'] === 'success') {
                $this->success('任务执行成功', $result);
            } else {
                $this->error('任务执行: ' . $result['message'], $result);
            }
        } catch (\Throwable $e) {
            $this->model->where('id', $id)->update(['output_msg' => '执行失败: ' . $e->getMessage()]);
            $this->error('任务执行失败: ' . $e->getMessage());
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
    
    /**
     * 处理列表数据
     * @param $data
     * @return void
     */
    protected function _index_list_filter(&$data): void
    {
        foreach ($data['data'] as &$vo) {
            try {
                $vo['exptime_info'] = \happy\admin\libs\helper\CronParserHelper::parse($vo['exptime']);
            } catch (LibsException $e) {
                $vo['exptime_info'] = $e->getMessage();
            }
        }
    }
}
