<?php

namespace app\command\system\spider;

use think\console\Command;

class DailyReport extends Command
{
    /**
     * 命令配置
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('spider:report-daily')
            ->setDescription('生成前一日蜘蛛抓取数据的统计报告，并写入统计表');
    }

    /**
     * 执行任务
     * @param \think\console\Input $input
     * @param \think\console\Output $output
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function execute(\think\console\Input $input, \think\console\Output $output)
    {
        // 获取昨天的日期
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // 查询昨日所有蜘蛛访问记录
        $rawData = (new \app\model\system\SystemSpiderLog())->whereBetweenTime('create_time', $yesterday . ' 00:00:00', $yesterday . ' 23:59:59')
            ->field([
                'spider_name',
                'url',
                'COUNT(*) AS visit_count',
                'MIN(create_time) AS first_visit',
                'MAX(create_time) AS last_visit'
            ])
            ->group('spider_name, url')
            ->select();

        if (!$rawData->isEmpty()) {
            $output->writeln("[$yesterday] 没有蜘蛛访问日志可供汇总");
            return;
        }

        // 准备统计数据
        $stats = [];

        // 先按蜘蛛名称分组
        $grouped = [];
        foreach ($rawData as $row) {
            $grouped[$row['name']][] = $row['url'];
        }

        foreach ($grouped as $spiderName => $urls) {
            $totalVisits = count($urls);
            $uniqueUrls = count(array_unique($urls));

            $stats[] = [
                'name' => $spiderName,
                'date' => $yesterday,
                'total_visits' => $totalVisits,
                'unique_urls' => $uniqueUrls,
            ];
        }

        // 写入统计表（批量插入）
        try {
            (new \app\model\system\SystemSpiderDate)->insertAll($stats);

            $output->writeln("✅ [$yesterday] 已成功生成蜘蛛抓取数据统计，共 " . count($stats) . " 条记录");
        } catch (\Exception $e) {
            $output->writeln("❌ 数据写入失败：" . $e->getMessage());
        }
    }
}
