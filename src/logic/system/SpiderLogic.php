<?php

namespace app\logic\system;

use app\model\system\SystemSpiderDate;
use app\model\system\SystemSpiderHourly;
use app\model\system\SystemSpiderLogs;
use mowzs\lib\BaseLogic;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class SpiderLogic extends BaseLogic
{
    /**
     * 获取今日蜘蛛爬取数据占比饼图的数据（基于 xz_system_spider_logs 表）
     * @return array|void
     */
    public function getTodaySpiderPieChartData()
    {
        $cacheKey = 'today_spider_pie_chart_data';
        try {
            $this->app->cache->remember($cacheKey, function () {
                // 查询今日蜘蛛日志，按 name 分组统计数量
                $result = SystemSpiderLogs::whereDay('create_time')
                    ->field(['name', 'COUNT(*) as total'])
                    ->group('name')
                    ->select()
                    ->toArray();
                // 处理数据格式为 ECharts 所需的格式
                $data = array_map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'value' => (int)$item['total']
                    ];
                }, $result);
                return $data;
            }, 60);
        } catch (\Throwable $e) {
            $this->app->log->error($e->getMessage());
            return [];
        }
    }

    /**
     * 获取昨日与今日各蜘蛛爬取对比数据
     *
     * @return array
     */
    public function getYesterdayAndTodayCompare(): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // 查询今日和昨日的数据
        $todayData = SystemSpiderDate::where('date', $today)
            ->column('total_visits', 'name');

        $yesterdayData = SystemSpiderDate::where('date', $yesterday)
            ->column('total_visits', 'name');

        $spiderNames = array_unique(array_merge(array_keys($todayData), array_keys($yesterdayData)));

        $compare = [];
        foreach ($spiderNames as $name) {
            $compare[] = [
                'name' => $name,
                'today' => $todayData[$name] ?? 0,
                'yesterday' => $yesterdayData[$name] ?? 0
            ];
        }

        return $compare;
    }

    /**
     * 获取今日与昨日蜘蛛总数对比
     *
     * @return array
     */
    public function getTotalVisitsCompare(): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $todayTotal = SystemSpiderDate::where('date', $today)->sum('total_visits');
        $yesterdayTotal = SystemSpiderDate::where('date', $yesterday)->sum('total_visits');

        return [
            'today' => (int)$todayTotal,
            'yesterday' => (int)$yesterdayTotal
        ];
    }

    /**
     * 获取当前小时与昨日同时段的对比数据
     *
     * @return array
     */
    public function getCurrentHourVsLastHourCompare(): array
    {
        $currentHour = date('H');
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $todayCurrentHourVisits = SystemSpiderHourly::where('date', $today)
            ->where('hour', $currentHour)
            ->sum('total_visits');

        $yesterdayCurrentHourVisits = SystemSpiderHourly::where('date', $yesterday)
            ->where('hour', $currentHour)
            ->sum('total_visits');

        return [
            'today' => (int)$todayCurrentHourVisits,
            'yesterday' => (int)$yesterdayCurrentHourVisits
        ];
    }

    /**
     * 获取各个蜘蛛最近七天的趋势
     *
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getRecentSevenDaysTrend(): array
    {
        $days = 7;
        $dateRange = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dateRange[] = date('Y-m-d', strtotime("-$i days"));
        }

        // 查询这些天的每日统计数据（按 spider name 分组）
        $dailyStats = SystemSpiderDate::where('date', 'in', $dateRange)
            ->order('date', 'asc')
            ->select()
            ->toArray();

        // 初始化每个蜘蛛每天的数据容器
        $spiderData = [];

        foreach ($dailyStats as $row) {
            $date = $row['date'];
            $spiderName = $row['name'];
            $totalVisits = (int)$row['total_visits'];

            if (!isset($spiderData[$spiderName])) {
                $spiderData[$spiderName] = array_fill(0, $days, 0);
            }

            $index = array_search($date, $dateRange);
            if ($index !== false) {
                $spiderData[$spiderName][$index] += $totalVisits;
            }
        }

        // 返回构造好的数据结构
        return [
            'dates' => $dateRange,
            'data' => $spiderData
        ];
    }
}
