<?php

namespace app\command\system\spider;

use think\console\Command;
use think\console\Input;
use think\console\InputArgument;
use think\console\Output;

class ClearLogs extends Command
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('spider:clear-logs')
            ->setDescription('清理 spider_log 表中超过指定天数的数据')
            ->addArgument('days', InputArgument::OPTIONAL, '保留数据的天数，默认为7天', 7);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|null
     */
    protected function execute(Input $input, Output $output): bool|int|null
    {
        // 获取用户输入的天数，默认为7天
        $days = (int)$input->getArgument('days');

        // 验证输入的天数是否有效
        if ($days <= 0) {
            $output->writeln("❌ 错误：天数必须大于0");
            return false;
        }

        // 计算指定天数前的时间点
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));

        try {
            // 删除指定天数前的数据
            $rowsDeleted = (new \app\model\system\SystemSpiderLogs())
                ->whereTime('create_time', '<', $cutoffDate)
                ->delete();

            if ($rowsDeleted > 0) {
                $output->writeln("✅ 已成功清理了 {$rowsDeleted} 条超过 {$days} 天的蜘蛛日志（保留最近 {$days} 天的数据）");
            } else {
                $output->writeln("✅ 没有超过 {$days} 天的蜘蛛日志需要清理（保留最近 {$days} 天的数据）");
            }

            return true;
        } catch (\Exception $e) {
            $output->writeln("❌ 清理失败：" . $e->getMessage());
            return false;
        }
    }
}
