<?php

namespace app\command\system\xuns;

use app\logic\search\XunSearchLogic;
use mowzs\lib\extend\RuntimeExtend;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;

class XunsAdd extends Command
{
    /**
     * @var string
     */
    protected string $jsonField = 'extend->xs';
    protected string $upJsonField = 'xs';
    /**
     * @var int[]
     */
    protected array $where = ['status' => 1, 'delete_time' => null];
    protected XunSearchLogic $xs;

    /**
     * 配置消息指令
     */
    protected function configure(): void
    {
        $this->setName('xuns:add');
        $this->addArgument('module', Argument::OPTIONAL, '模块名称', 'article');
        $this->setDescription('XunSearch数据入库');
    }


    /**
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException|\XSException
     */
    protected function execute(Input $input, Output $output): void
    {
        $this->xs = new XunSearchLogic();
        $output->info('开始处理XunSearch数据');
        $module = $input->getArgument('module');
        $model_table = $module . '_model';
        if (!RuntimeExtend::checkRoute()) {
            Log::error('当前命令【xuns:add】可执行条件不足-Route');
            $output->info('当前命令【xuns:add】可执行条件不足-Route');
            return;
        }
        $output->info('开始处理模块：' . $module);
        $modles = $this->app->db->name($model_table)->where('id', '>', 0)->select()->toArray();
        foreach ($modles as $model) {
            $output->info('开始处理模块：' . $module . $model['title'] . '模型');
            $content_table = $module . '_content_' . $model['id'];
            $content_data = $this->app->db->name($content_table)->json(['extend'])->where($this->where)->where(function ($query) {
                $query->where($this->jsonField, 0)->whereOr($this->jsonField, null);
            })->field('id,title,images,create_time')->cursor();
            $k = 0;
            $count = $this->app->db->name($content_table)->json(['extend'])->where($this->where)->where(function ($query) {
                $query->where($this->jsonField, 0)->whereOr($this->jsonField, null);
            })->count();
            foreach ($content_data as $data) {
                $this->xs->add([
                    'id' => $module . '_' . $data['id'],
                    'title' => $data['title'],
                    'content' => get_word(del_html($this->app->db->name($content_table . 's')->where('id', $data['id'])->value('content')), 300),
                    'module' => $module,
                    'aid' => $data['id'],
                    'images' => $data['images'],
                    'create_time' => $data['create_time'],
                    'status' => 1,
                    'url' => hurl($module . '/content/index', ['id' => $data['id']]),
                ]);
                $up_data[$this->jsonField] = 1;
                $this->app->db->name($content_table)->json(['extend'])->where('id', $data['id'])->update($up_data);
                $output->info("[{$count}/{$k}]模块:{$module} 模型:{$model['title']} 模型id:{$model['id']} 内容id {$data['id']} 添加成功");
                $k++;
            }
        }
    }

}
