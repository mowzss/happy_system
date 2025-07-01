<?php

namespace app\command\system\spider;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class ClearLogs extends Command
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('spider:clear-logs')
            ->setDescription('清理 spider_log 表中超过一周的数据');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|null
     */
    protected function execute(Input $input, Output $output): bool|int|null
    {
        // 计算一周前的时间点
        $oneWeekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));

        try {
            // 删除一周前的数据
            $rowsDeleted = (new \app\model\system\SystemSpiderLog())
                ->where('create_time', '<', $oneWeekAgo)
                ->delete();

            if ($rowsDeleted > 0) {
                $output->writeln("✅ 成功清理了 {$rowsDeleted} 条超过一周的蜘蛛日志");
            } else {
                $output->writeln("✅ 没有超过一周的蜘蛛日志需要清理");
            }

            return true;
        } catch (\Exception $e) {
            $output->writeln("❌ 清理失败：" . $e->getMessage());
            return false;
        }
    }
}
