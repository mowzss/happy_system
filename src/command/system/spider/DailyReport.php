<?php

namespace app\command\system\spider;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class DailyReport extends Command
{
    protected function configure(): void
    {
        $this->setName('spider:report-daily')
            ->setDescription('基于每小时统计数据生成蜘蛛抓取每日汇总报告')
            ->addOption('date', null, Option::VALUE_REQUIRED, '指定要处理的日期，格式 YYYY-MM-DD');
    }

    protected function execute(Input $input, Output $output): void
    {
        // 获取用户传入的日期，或默认使用昨天
        $customDate = $input->getOption('date');

        if ($customDate) {
            // 验证格式是否正确
            $date = \DateTime::createFromFormat('Y-m-d', $customDate);
            if (!$date || $date->format('Y-m-d') !== $customDate) {
                $output->writeln("❌ 日期格式错误，应为 YYYY-MM-DD：" . $customDate);
                return;
            }
            $yesterday = $customDate;
        } else {
            // 默认处理昨天的数据
            $yesterday = date('Y-m-d', strtotime('-1 day'));
        }

        $output->writeln("🔍 开始生成 [$yesterday] 的蜘蛛每日汇总...");

        // 查询该天所有小时记录，并按 name 聚合
        $hourlyStats = (new \app\model\system\SystemSpiderHourly())
            ->where('date', $yesterday)
            ->field([
                'name',
                'SUM(total_visits)' => 'total_visits',
                'SUM(unique_urls)' => 'unique_urls'
            ])
            ->group('name')
            ->select()
            ->toArray();

        if (empty($hourlyStats)) {
            $output->writeln("✅ [$yesterday] 没有找到对应的每小时统计数据");
            return;
        }

        // 添加日期字段并去重/检查是否已存在
        $modelDaily = new \app\model\system\SystemSpiderDate();
        $toInsert = [];

        foreach ($hourlyStats as $item) {
            $exists = $modelDaily
                ->where('name', $item['name'])
                ->where('date', $yesterday)
                ->find();

            if ($exists) {
                $output->writeln("⚠️ [$yesterday] 蜘蛛【{$item['name']}】的统计已存在，跳过插入");
                continue;
            }

            $item['date'] = $yesterday;
            $toInsert[] = $item;
        }

        if (empty($toInsert)) {
            $output->writeln("✅ [$yesterday] 所有蜘蛛统计均已存在，无需插入");
            return;
        }

        try {
            // 批量插入
            $modelDaily->insertAll($toInsert);
            $output->writeln("✅ [$yesterday] 成功写入 " . count($toInsert) . " 条蜘蛛每日统计");
        } catch (\Exception $e) {
            $output->writeln("❌ [$yesterday] 数据写入失败：" . $e->getMessage());
        }
    }
}
