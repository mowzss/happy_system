<?php


namespace app\command\system\spider;

use think\console\Input;
use think\console\Output;
use think\console\Command;
use think\console\input\Option;

class HourlyReport extends Command
{
    protected function configure()
    {
        $this->setName('spider:report-hourly')
            ->setDescription('生成指定或上一小时的蜘蛛抓取统计数据，并写入小时统计表')
            ->addOption('time', null, Option::VALUE_OPTIONAL, '指定要处理的时间，格式为 YYYY-MM-DD HH');
    }
    
    protected function execute(Input $input, Output $output)
    {
        // 获取用户传入的时间，或默认使用上一小时
        $customTime = $input->getOption('time');
        
        if ($customTime) {
            // 用户指定了时间，尝试解析
            $hourAgo = strtotime($customTime . ":00");
            if (!$hourAgo || date("Y-m-d H", $hourAgo) > date("Y-m-d H")) {
                $output->writeln("❌ 时间格式错误或时间大于当前时间：" . $customTime);
                return;
            }
            $statDate = date("Y-m-d", $hourAgo);
            $statHour = (int)date("H", $hourAgo);
        } else {
            // 自动处理上一小时
            $hourAgo = strtotime("-1 hour");
            $statDate = date("Y-m-d", $hourAgo);
            $statHour = (int)date("H", $hourAgo);
        }
        
        $startTime = "$statDate {$statHour}:00:00";
        $endTime = "$statDate {$statHour}:59:59";
        
        $output->writeln("🔍 开始处理 [$startTime ~ $endTime] 的蜘蛛访问数据...");
        
        $modelLog = new \app\model\system\SystemSpiderLogs();
        $modelStats = new \app\model\system\SystemSpiderHourly();
        
        // 查询该小时内所有蜘蛛访问记录，按 spider_code 分组并统计
        $stats = $modelLog
            ->alias('l')
            ->field([
                'name',
                'COUNT(*)' => 'total_visits',
                'COUNT(DISTINCT url)' => 'unique_urls',
            ])
            ->whereBetweenTime('create_time', $startTime, $endTime)
            ->group('name')
            ->select()
            ->toArray();
        
        if (empty($stats)) {
            $output->writeln("✅ [{$statDate} {$statHour}:00] 没有蜘蛛访问日志");
            return;
        }
        
        // 添加 date 和 hour 字段
        foreach ($stats as &$item) {
            $item['date'] = $statDate;
            $item['hour'] = $statHour;
        }
        
        try {
            // 插入前检查是否已存在该蜘蛛在该小时的记录，避免重复插入
            unset($item);
            foreach ($stats as $item) {
                $exists = $modelStats
                    ->where('name', $item['name'])
                    ->where('date', $item['date'])
                    ->where('hour', $item['hour'])
                    ->find();
                
                if ($exists) {
                    $output->writeln("⚠️ [{$statDate} {$statHour}:00] 蜘蛛【{$item['name']}】已存在，跳过插入");
                    continue;
                }
                
                $modelStats->insert($item);
                $output->writeln("✅ [{$statDate} {$statHour}:00] 成功写入：{$item['name']}");
            }
        } catch (\Exception $e) {
            $output->writeln("❌ [{$statDate} {$statHour}:00] 写入失败：" . $e->getMessage());
        }
    }
}
