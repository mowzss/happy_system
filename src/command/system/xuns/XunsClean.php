<?php

namespace app\command\system\xuns;

use app\logic\search\XunSearchLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class XunsClean extends Command
{
    protected XunSearchLogic $xs;
    /**
     * @var string
     */
    protected string $jsonField = 'extend->xs';
    /**
     * @var string
     */
    protected string $upJsonField = 'xs';

    /**
     * 配置消息指令
     */
    protected function configure(): void
    {
        $this->setName('xuns:clean');
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
        $this->xs = XunSearchLogic::instance();
        $output->info('开始清理数据索引');
        $this->xs->clearAll();
        $modules = $this->app->db->name('system_module')->where('status', 1)->select()->toArray();
        foreach ($modules as $module_info) {
            $module = $module_info['dir'];
            if (sys_config($module . '.is_open_search', 0)) {
                $model_table = $module . '_model';
                $modles = $this->app->db->name($model_table)->where('id', '>', 0)->select()->toArray();
                foreach ($modles as $model) {
                    $output->info("开始处理模块:{$module}  模型:{$model['title']} 模型id:{$model['id']}");
                    $content_table = $module . '_content_' . $model['id'];
                    $up_data['extend'][$this->upJsonField] = 0;
                    $this->app->db->name($content_table)->json(['extend'])->where(function ($query) {
                        $query->where($this->jsonField, 1)->whereOr($this->jsonField, null);
                    })->update($up_data);
                    $output->info("模块:{$module} 模型:{$model['title']} 模型id:{$model['id']} 状态修改成功");
                }
            } else {
                $output->info('模块：' . $module . '未开启搜索功能,已跳过!');
            }
        }

    }
}
