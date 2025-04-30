<?php

namespace app\command\system\indexnow;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class IndexNowClean extends Command
{
    /**
     * @var string
     */
    protected string $jsonField = 'extend->index_now';
    /**
     * @var string
     */
    protected string $upJsonField = 'index_now';

    /**
     * 配置消息指令
     */
    protected function configure(): void
    {
        $this->setName('indexnow:clean');
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
        $output->info('开始重置推送记录');
        $modules = explode(',', sys_config('p_index_now.open_module'));
        foreach ($modules as $module) {
            $model_table = $module . '_model';
            $models = $this->app->db->name($model_table)->where('id', '>', 0)->select()->toArray();
            foreach ($models as $model) {
                $output->info("开始处理模块:{$module}  模型:{$model['title']} 模型id:{$model['id']}");
                $content_table = $module . '_content_' . $model['id'];
                $up_data['extend'][$this->upJsonField] = 0;
                $this->app->db->name($content_table)->json(['extend'])->where(function ($query) {
                    $query->where($this->jsonField, 1)->whereOr($this->jsonField, null);
                })->update($up_data);
                $output->info("模块:{$module} 模型:{$model['title']} 模型id:{$model['id']} 状态修改成功");
            }

        }

    }
}
