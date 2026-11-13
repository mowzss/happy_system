<?php


namespace app\logic\system;

use happy\admin\libs\BaseLogic;
use app\model\system\SystemSpiderDate;
use app\model\system\SystemSpiderHourly;
use app\model\system\SystemSpiderLogs;

class SpiderLogic extends BaseLogic
{
    /**
     * 蜘蛛标识 => 展示名称
     */
    protected const SPIDER_LABELS = [
        'google'  => 'Google',
        'bing'    => 'Bing',
        'baidu'   => '百度',
        'sogou'   => '搜狗',
        'so_360'  => '360',
        'haosou'  => '好搜',
        'yisou'   => '神马',
        'toutiao' => '头条',
        'douyin'  => '抖音',
    ];
    
    /**
     * 看板数据缓存时长（秒）
     */
    protected const CACHE_TTL = 60;
    
    /**
     * 趋势图最多展示的蜘蛛数量，其余合并为「其他」
     */
    protected const TREND_MAX_SERIES = 6;
    
    /**
     * 蜘蛛统计看板数据
     *
     * @param int $days 统计天数
     * @param int $logRows 最新记录条数
     * @return array
     */
    public function getDashboard(int $days = 7, int $logRows = 12): array
    {
        $cacheKey = 'spider_dashboard_' . $days . '_' . $logRows;
        $cached = $this->app->cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
        
        $data = $this->buildDashboard($days, $logRows);
        $this->app->cache->set($cacheKey, $data, self::CACHE_TTL);
        
        return $data;
    }
    
    /**
     * 组装看板数据
     *
     * @param int $days
     * @param int $logRows
     * @return array
     */
    protected function buildDashboard(int $days, int $logRows): array
    {
        $today = date('Y-m-d');
        $todayAggregate = $this->getLogAggregate($today);
        $baseDate = $this->resolveBaseDate($todayAggregate, $today);
        $prevDate = date('Y-m-d', strtotime($baseDate . ' -1 day'));
        
        // 统计区间（含基准日）
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = date('Y-m-d', strtotime($baseDate . ' -' . $i . ' day'));
        }
        
        // 历史日期取每日汇总表，基准日以实时日志为准
        $daily = $this->getDailyAggregate($dates, $baseDate);
        $daily[$baseDate] = $baseDate === $today ? $todayAggregate : $this->getLogAggregate($baseDate);
        
        $ranking = $this->buildRanking($daily, $dates, $baseDate, $prevDate);
        $rows = $ranking['rows'];
        $lastLog = $this->getLastLogInfo();
        
        $baseLabel = date('m-d', strtotime($baseDate));
        $prevLabel = date('m-d', strtotime($prevDate));
        if ($baseDate === $today) {
            $baseLabel = '今日 ' . $baseLabel;
            $prevLabel = '昨日 ' . $prevLabel;
        }
        
        return [
            'status'      => [
                'base_date'     => $baseDate,
                'prev_date'     => $prevDate,
                'is_today'      => $baseDate === $today,
                'base_label'    => $baseLabel,
                'prev_label'    => $prevLabel,
                'last_log_time' => $lastLog['time'],
                'last_log_ago'  => $lastLog['ago'],
            ],
            'overview'    => $this->buildOverview($daily, $dates, $baseDate, $prevDate, $ranking['total_seven']),
            'ranking'     => $rows,
            'composition' => $this->buildComposition($baseDate),
            'logs'        => $this->getRecentLogs($logRows),
            'charts'      => [
                'base_label' => $baseLabel,
                'prev_label' => $prevLabel,
                'trend'      => $this->buildTrend($daily, $dates, $rows),
                'share'      => $this->buildShare($rows),
                'compare'    => $this->buildCompare($rows),
                'hourly'     => $this->buildHourly($baseDate, $prevDate, $rows),
            ],
        ];
    }
    
    /**
     * 解析统计基准日：今日有数据用今日，否则回退到最近有记录的日期
     *
     * @param array $todayAggregate 今日实时统计
     * @param string $today
     * @return string
     */
    protected function resolveBaseDate(array $todayAggregate, string $today): string
    {
        if (!empty($todayAggregate)) {
            return $today;
        }
        
        $lastTime = (int)SystemSpiderLogs::max('create_time');
        
        return $lastTime > 0 ? date('Y-m-d', $lastTime) : $today;
    }
    
    /**
     * 获取最近一次抓取记录时间
     *
     * @return array
     */
    protected function getLastLogInfo(): array
    {
        $lastTime = (int)SystemSpiderLogs::max('create_time');
        if ($lastTime <= 0) {
            return ['time' => '暂无记录', 'ago' => '—'];
        }
        
        return [
            'time' => date('Y-m-d H:i:s', $lastTime),
            'ago'  => $this->timeAgo($lastTime),
        ];
    }
    
    /**
     * 按天统计日志（名称 => 访问量 / 独立 URL）
     *
     * @param string $date
     * @return array
     */
    protected function getLogAggregate(string $date): array
    {
        [$start, $end] = $this->dayRange($date);
        
        $rows = SystemSpiderLogs::where('create_time', '>=', $start)
            ->where('create_time', '<', $end)
            ->field('name, COUNT(*) AS total, COUNT(DISTINCT url) AS unique_urls')
            ->group('name')
            ->select()
            ->toArray();
        
        $map = [];
        foreach ($rows as $row) {
            $map[$row['name']] = [
                'total'  => (int)$row['total'],
                'unique' => (int)$row['unique_urls'],
            ];
        }
        
        return $map;
    }
    
    /**
     * 读取每日汇总数据，汇总表缺失的日期回退到实时日志
     *
     * @param array $dates
     * @param string $baseDate
     * @return array
     */
    protected function getDailyAggregate(array $dates, string $baseDate): array
    {
        $rows = SystemSpiderDate::whereIn('date', $dates)
            ->field(['date', 'name', 'total_visits', 'unique_urls'])
            ->select()
            ->toArray();
        
        $map = [];
        foreach ($rows as $row) {
            $map[$row['date']][$row['name']] = [
                'total'  => (int)$row['total_visits'],
                'unique' => (int)$row['unique_urls'],
            ];
        }
        
        foreach ($dates as $date) {
            if ($date === $baseDate || !empty($map[$date])) {
                continue;
            }
            $map[$date] = $this->getLogAggregate($date);
        }
        
        return $map;
    }
    
    /**
     * 构建蜘蛛排行
     *
     * @param array $daily
     * @param array $dates
     * @param string $baseDate
     * @param string $prevDate
     * @return array
     */
    protected function buildRanking(array $daily, array $dates, string $baseDate, string $prevDate): array
    {
        $names = [];
        foreach ($daily as $map) {
            foreach ($map as $name => $item) {
                $names[$name] = true;
            }
        }
        
        $rows = [];
        $totalSeven = 0;
        foreach (array_keys($names) as $name) {
            $base = $daily[$baseDate][$name] ?? ['total' => 0, 'unique' => 0];
            $prev = $daily[$prevDate][$name] ?? ['total' => 0, 'unique' => 0];
            
            $seven = 0;
            foreach ($dates as $date) {
                $seven += $daily[$date][$name]['total'] ?? 0;
            }
            $totalSeven += $seven;
            
            $change = $this->changeRate($base['total'], $prev['total']);
            
            $rows[] = [
                'name'         => $name,
                'label'        => $this->spiderLabel($name),
                'base'         => $base['total'],
                'base_text'    => number_format($base['total']),
                'prev'         => $prev['total'],
                'prev_text'    => number_format($prev['total']),
                'unique'       => $base['unique'],
                'unique_text'  => number_format($base['unique']),
                'seven'        => $seven,
                'seven_text'   => number_format($seven),
                'change'       => $change,
                'change_text'  => $this->changeText($change),
                'change_class' => $this->changeClass($change),
            ];
        }
        
        usort($rows, fn ($a, $b) => $b['seven'] <=> $a['seven']);
        
        foreach ($rows as &$row) {
            $row['share'] = $totalSeven > 0 ? round($row['seven'] / $totalSeven * 100, 1) : 0.0;
            $row['share_text'] = $totalSeven > 0 ? $row['share'] . '%' : '—';
        }
        unset($row);
        
        return ['rows' => $rows, 'total_seven' => $totalSeven];
    }
    
    /**
     * 汇总概览指标
     *
     * @param array $daily
     * @param array $dates
     * @param string $baseDate
     * @param string $prevDate
     * @param int $totalSeven
     * @return array
     */
    protected function buildOverview(array $daily, array $dates, string $baseDate, string $prevDate, int $totalSeven): array
    {
        $baseTotal = array_sum(array_column($daily[$baseDate] ?? [], 'total'));
        $baseUnique = array_sum(array_column($daily[$baseDate] ?? [], 'unique'));
        $prevTotal = array_sum(array_column($daily[$prevDate] ?? [], 'total'));
        
        $change = $this->changeRate($baseTotal, $prevTotal);
        
        return [
            'base_total'      => $baseTotal,
            'base_total_text' => number_format($baseTotal),
            'prev_total'      => $prevTotal,
            'prev_total_text' => number_format($prevTotal),
            'seven_total'     => $totalSeven,
            'seven_total_text' => number_format($totalSeven),
            'day_avg_text'    => number_format((int)round($totalSeven / max(count($dates), 1))),
            'base_unique'     => $baseUnique,
            'base_unique_text' => number_format($baseUnique),
            'unique_rate'     => $baseTotal > 0 ? round($baseUnique / $baseTotal * 100, 1) : 0.0,
            'change'          => $change,
            'change_text'     => $this->changeText($change),
            'change_class'    => $this->changeClass($change),
        ];
    }
    
    /**
     * 近 N 天趋势（Top N 蜘蛛 + 其他）
     *
     * @param array $daily
     * @param array $dates
     * @param array $rows
     * @return array
     */
    protected function buildTrend(array $daily, array $dates, array $rows): array
    {
        $topRows = array_slice($rows, 0, self::TREND_MAX_SERIES);
        $topNames = array_column($topRows, 'name');
        
        $series = [];
        foreach ($topRows as $row) {
            $data = [];
            foreach ($dates as $date) {
                $data[] = $daily[$date][$row['name']]['total'] ?? 0;
            }
            $series[] = ['name' => $row['label'], 'data' => $data];
        }
        
        $rest = array_fill(0, count($dates), 0);
        foreach ($rows as $row) {
            if (in_array($row['name'], $topNames, true)) {
                continue;
            }
            foreach ($dates as $index => $date) {
                $rest[$index] += $daily[$date][$row['name']]['total'] ?? 0;
            }
        }
        if (array_sum($rest) > 0) {
            $series[] = ['name' => '其他', 'data' => $rest];
        }
        
        return [
            'dates'  => array_map(fn ($date) => date('m-d', strtotime($date)), $dates),
            'series' => $series,
        ];
    }
    
    /**
     * 蜘蛛占比（按近 N 天抓取量）
     *
     * @param array $rows
     * @return array
     */
    protected function buildShare(array $rows): array
    {
        $share = [];
        foreach ($rows as $row) {
            if ($row['seven'] <= 0) {
                continue;
            }
            $share[] = ['name' => $row['label'], 'value' => $row['seven']];
        }
        
        return $share;
    }
    
    /**
     * 基准日与前一日对比
     *
     * @param array $rows
     * @return array
     */
    protected function buildCompare(array $rows): array
    {
        $names = [];
        $base = [];
        $prev = [];
        foreach ($rows as $row) {
            if ($row['base'] === 0 && $row['prev'] === 0) {
                continue;
            }
            $names[] = $row['label'];
            $base[] = $row['base'];
            $prev[] = $row['prev'];
        }
        
        return ['names' => $names, 'base' => $base, 'prev' => $prev];
    }
    
    /**
     * 基准日与前一日分时趋势
     *
     * @param string $baseDate
     * @param string $prevDate
     * @param array $rows
     * @return array
     */
    protected function buildHourly(string $baseDate, string $prevDate, array $rows): array
    {
        $records = SystemSpiderHourly::whereIn('date', [$baseDate, $prevDate])
            ->field(['name', 'date', 'hour', 'total_visits'])
            ->select()
            ->toArray();
        
        $baseMap = [];
        $prevMap = [];
        foreach ($records as $record) {
            $hour = (int)$record['hour'];
            if ($record['date'] === $baseDate) {
                $baseMap[$record['name']][$hour] = (int)$record['total_visits'];
            } else {
                $prevMap[$record['name']][$hour] = (int)$record['total_visits'];
            }
        }
        
        $spiderData = [];
        foreach (array_column($rows, 'name') as $name) {
            if (!isset($baseMap[$name]) && !isset($prevMap[$name])) {
                continue;
            }
            $spiderData[] = [
                'name' => $this->spiderLabel($name),
                'base' => $this->fillHours($baseMap[$name] ?? []),
                'prev' => $this->fillHours($prevMap[$name] ?? []),
            ];
        }
        
        return [
            'hours'      => array_map(fn ($hour) => sprintf('%02d:00', $hour), range(0, 23)),
            'spiderData' => $spiderData,
        ];
    }
    
    /**
     * 基准日抓取构成：设备 + 频道
     *
     * @param string $baseDate
     * @return array
     */
    protected function buildComposition(string $baseDate): array
    {
        [$start, $end] = $this->dayRange($baseDate);
        
        $baseQuery = fn () => SystemSpiderLogs::where('create_time', '>=', $start)
            ->where('create_time', '<', $end);
        
        // 设备分布
        $deviceRows = $baseQuery()->field('device, COUNT(*) AS total')->group('device')->select()->toArray();
        $device = [];
        $baseTotal = 0;
        foreach ($deviceRows as $row) {
            $total = (int)$row['total'];
            $baseTotal += $total;
            $label = match ((int)$row['device']) {
                1 => 'PC 端',
                2 => '移动端',
                default => '未知设备',
            };
            $device[$label] = ['label' => $label, 'total' => ($device[$label]['total'] ?? 0) + $total];
        }
        
        // 频道分布 Top 6
        $moduleRows = $baseQuery()
            ->field('module, COUNT(*) AS total')
            ->group('module')
            ->order('total', 'desc')
            ->limit(6)
            ->select()
            ->toArray();
        
        $moduleLabels = $this->moduleLabels();
        $module = [];
        foreach ($moduleRows as $row) {
            $module[] = [
                'label' => $moduleLabels[$row['module']] ?? ($row['module'] ?: '未知'),
                'total' => (int)$row['total'],
            ];
        }
        
        $format = function (array $items, int $total): array {
            foreach ($items as &$item) {
                $item['total_text'] = number_format($item['total']);
                $item['percent'] = $total > 0 ? round($item['total'] / $total * 100, 1) : 0.0;
            }
            unset($item);
            
            return $items;
        };
        
        return [
            'device' => $format(array_values($device), $baseTotal),
            'module' => $format($module, $baseTotal),
        ];
    }
    
    /**
     * 最新抓取记录
     *
     * @param int $rows
     * @return array
     */
    protected function getRecentLogs(int $rows): array
    {
        $logs = SystemSpiderLogs::order('id', 'desc')->limit($rows)->select()->toArray();
        $moduleLabels = $this->moduleLabels();
        
        foreach ($logs as &$log) {
            $timestamp = is_numeric($log['create_time'])
                ? (int)$log['create_time']
                : (int)strtotime((string)$log['create_time']);
            
            $log['label'] = $this->spiderLabel($log['name']);
            $log['device_text'] = match ((int)$log['device']) {
                1 => 'PC',
                2 => '移动',
                default => '未知',
            };
            $log['module_text'] = $moduleLabels[$log['module']] ?? ($log['module'] ?: '未知');
            $log['url_full'] = ($log['domain'] ?? '') . $log['url'];
            $log['time_text'] = $timestamp > 0 ? date('m-d H:i:s', $timestamp) : '—';
            $log['ago'] = $timestamp > 0 ? $this->timeAgo($timestamp) : '—';
            
            try {
                $log['isp'] = (new \Ip2Region())->simple($log['ip']);
            } catch (\Throwable $e) {
                $log['isp'] = '未知';
            }
        }
        unset($log);
        
        return $logs;
    }
    
    /**
     * 模块目录 => 名称
     *
     * @return array
     */
    protected function moduleLabels(): array
    {
        static $labels = null;
        if ($labels === null) {
            $labels = ModuleLogic::instance()->getModuleAll();
        }
        
        return $labels;
    }
    
    /**
     * 蜘蛛标识 => 展示名称
     *
     * @param string $name
     * @return string
     */
    protected function spiderLabel(string $name): string
    {
        return self::SPIDER_LABELS[$name] ?? ($name !== '' ? $name : '未知蜘蛛');
    }
    
    /**
     * 日期的起止时间戳
     *
     * @param string $date
     * @return int[]
     */
    protected function dayRange(string $date): array
    {
        $start = (int)strtotime($date . ' 00:00:00');
        
        return [$start, $start + 86400];
    }
    
    /**
     * 补齐 24 小时数据
     *
     * @param array $map
     * @return array
     */
    protected function fillHours(array $map): array
    {
        $data = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $data[] = $map[$hour] ?? 0;
        }
        
        return $data;
    }
    
    /**
     * 计算环比（前一日为 0 时返回 null）
     *
     * @param int $base
     * @param int $prev
     * @return float|null
     */
    protected function changeRate(int $base, int $prev): ?float
    {
        if ($prev === 0) {
            return null;
        }
        
        return round(($base - $prev) / $prev * 100, 1);
    }
    
    /**
     * 环比文案
     *
     * @param float|null $change
     * @return string
     */
    protected function changeText(?float $change): string
    {
        if ($change === null) {
            return '—';
        }
        
        return ($change > 0 ? '+' : '') . $change . '%';
    }
    
    /**
     * 环比样式标识
     *
     * @param float|null $change
     * @return string
     */
    protected function changeClass(?float $change): string
    {
        if ($change === null || $change == 0.0) {
            return 'flat';
        }
        
        return $change > 0 ? 'up' : 'down';
    }
    
    /**
     * 相对时间
     *
     * @param int $timestamp
     * @return string
     */
    protected function timeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return '刚刚';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' 分钟前';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' 小时前';
        }
        if ($diff < 86400 * 30) {
            return floor($diff / 86400) . ' 天前';
        }
        
        return date('Y-m-d', $timestamp);
    }
}
