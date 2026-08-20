<?php

namespace app\command\system\nav;

use think\console\Command;
use app\logic\system\NavLogic;
use happy\admin\libs\Exception\LogicException;

class NavRestNodeUrl extends Command
{
    protected function configure()
    {
        $this->setName('nav:rest_node_url');
        $this->setDescription('重置网站导航节点的URL');
    }
    
    /**
     * 执行命令
     * @param \think\console\Input $input
     * @param \think\console\Output $output
     * @return void
     */
    protected function execute(\think\console\Input $input, \think\console\Output $output)
    {
        try {
            $output->info('开始重置网站导航节点的URL');
            NavLogic::instance()->setNodeUrl();
            
        } catch (LogicException $e) {
            $output->error($e->getMessage());
        }
    }
}
