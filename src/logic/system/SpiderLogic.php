<?php

namespace app\logic\system;

use app\model\system\SystemSpiderDate;
use app\model\system\SystemSpiderHourly;
use mowzs\lib\BaseLogic;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class SpiderLogic extends BaseLogic
{
    /**
     * @param int $rows
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getNewLogs(int $rows = 10): array
    {
        return (new \app\model\system\SystemSpiderLogs)->limit($rows)->order('id', 'desc')->select()->each(function ($rs) {
            try {
                $rs['isp'] = (new \Ip2Region())->simple($rs['ip']);
            } catch (\Exception $e) {
                $rs['isp'] = '未知';
            }
            return $rs;
        })->toArray();
    }

    /**
     * 获取今日蜘蛛爬取数据占比饼图的数据（基于 xz_system_spider_logs 表）
     * @return array|mixed
     */
    public function getTodaySpiderPieChartData(): mixed
    {
        $cacheKey = 'today_spider_pie_chart_data';
        try {
            return $this->app->cache->remember($cacheKey, function () {
                // 查询今日蜘蛛日志，按 name 分组统计数量
                $result = (new \app\model\system\SystemSpiderLogs)->whereDay('create_time')
                    ->field(['name', 'COUNT(*) as total'])
                    ->group('name')
                    ->select()
                    ->toArray();
                // 处理数据格式为 ECharts 所需的格式
                return array_map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'value' => (int)$item['total']
                    ];
                }, $result);
            }, 60);
        } catch (\Throwable $e) {
            $this->app->log->error($e->getMessage());
            return [];
        }
    }

    /**
     * 获取昨日与今日各蜘蛛爬取对比数据（以蜘蛛为组，今日为实时数据）
     *
     * @return array
     */
    public function getYesterdayAndTodayCompare(): array
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // 查询今日实时数据（来自 logs 表）
        $todayData = $this->getTodaySpiderPieChartData();

        // 查询昨日统计数据（来自 SystemSpiderDate）
        $yesterdayData = SystemSpiderDate::where('date', $yesterday)
            ->column('total_visits', 'name');

        // 构建今日数据 map
        $todayMap = [];
        foreach ($todayData as $item) {
            $todayMap[$item['name']] = $item['value'];
        }

        // 合并所有蜘蛛名称
        $allSpiders = array_unique(array_merge(
            array_keys($todayMap),
            array_keys($yesterdayData)
        ));

        // 构造返回结果
        $compare = [];
        foreach ($allSpiders as $name) {
            $compare[] = [
                'name' => $name,
                'today' => $todayMap[$name] ?? 0,
                'yesterday' => $yesterdayData[$name] ?? 0
            ];
        }

        return $compare;
    }


    /**
     * 获取今日与昨日每小时蜘蛛爬取趋势，并按蜘蛛名称分组汇总（带缓存）
     *
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getHourlyTrendTodayVsYesterdayBySpider(): array
    {
        $cacheKey = 'hourly_trend_today_vs_yesterday_by_spider';
        $cacheTTL = 1200; // 缓存时间：20分钟

        // 尝试从缓存读取数据
        $cachedData = $this->app->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        // 如果没有缓存，则执行查询逻辑
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // 获取今日和昨日的所有蜘蛛名称集合
        $allNames = SystemSpiderHourly::whereIn('date', [$today, $yesterday])
            ->distinct(true)
            ->column('name');

        if (empty($allNames)) {
            $result = [
                'hours' => array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23)),
                'spiderData' => []
            ];

            // 写入缓存
            $this->app->cache->set($cacheKey, $result, $cacheTTL);

            return $result;
        }

        $hours = range(0, 23);
        $defaultData = array_fill(0, 24, 0);

        $spiderData = [];

        foreach ($allNames as $name) {
            // 查询今日该蜘蛛的每小时数据
            $todayData = SystemSpiderHourly::where('date', $today)
                ->where('name', $name)
                ->field(['hour', 'total_visits'])
                ->order('hour', 'asc')
                ->select()
                ->toArray();

            // 查询昨日该蜘蛛的每小时数据
            $yesterdayData = SystemSpiderHourly::where('date', $yesterday)
                ->where('name', $name)
                ->field(['hour', 'total_visits'])
                ->order('hour', 'asc')
                ->select()
                ->toArray();

            // 初始化默认值
            $todayMap = $defaultData;
            foreach ($todayData as $item) {
                $hour = (int)$item['hour'];
                $todayMap[$hour] = (int)$item['total_visits'];
            }

            $yesterdayMap = $defaultData;
            foreach ($yesterdayData as $item) {
                $hour = (int)$item['hour'];
                $yesterdayMap[$hour] = (int)$item['total_visits'];
            }

            $spiderData[] = [
                'name' => $name,
                'today' => $todayMap,
                'yesterday' => $yesterdayMap
            ];
        }

        // 返回小时标签
        $hourLabels = array_map(function ($h) {
            return sprintf('%02d:00', $h);
        }, range(0, 23));

        $result = [
            'hours' => $hourLabels,
            'spiderData' => $spiderData
        ];

        // 写入缓存
        $this->app->cache->set($cacheKey, $result, $cacheTTL);

        return $result;
    }

    /**
     * 获取各个蜘蛛最近七天的趋势（今日数据为实时日志，其余使用历史汇总）
     *
     * @param int $days
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getRecentSevenDaysTrend($days = 7): array
    {
        $dateRange = [];

        // 构建日期范围：包含今天在内的过去 7 天
        for ($i = $days - 1; $i >= 0; $i--) {
            $dateRange[] = date('Y-m-d', strtotime("-$i days"));
        }

        // 查询今日实时数据（来自 Spider Logs）
        $todayData = $this->getTodaySpiderPieChartData();

        // 查询过去 6 天的历史数据（来自 SystemSpiderDate）
        $historyDates = array_slice($dateRange, 0, $days - 1); // 去掉最后一个（今天）

        $historyData = SystemSpiderDate::where('date', 'in', $historyDates)
            ->order('date', 'asc')
            ->select()
            ->toArray();

        // 合并数据结构
        $spiderTrend = [];

        // 处理今日数据
        foreach ($todayData as $item) {
            $name = $item['name'];
            if (!isset($spiderTrend[$name])) {
                $spiderTrend[$name] = array_fill(0, $days, 0);
            }
            $spiderTrend[$name][$days - 1] = $item['value']; // 最后一个是今天
        }

        // 处理历史数据
        foreach ($historyData as $item) {
            $name = $item['name'];
            $date = $item['date'];

            // 找到对应的 index
            $index = array_search($date, $historyDates);

            if ($index === false) continue;

            if (!isset($spiderTrend[$name])) {
                $spiderTrend[$name] = array_fill(0, $days, 0);
            }

            $spiderTrend[$name][$index] += (int)$item['total_visits'];
        }

        // 返回最终结构
        return [
            'dates' => $dateRange,
            'data' => $spiderTrend
        ];
    }
}
