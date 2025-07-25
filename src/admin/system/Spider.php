<?php

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\logic\system\SpiderLogic;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class Spider extends BaseAdmin
{
    use CrudTrait;

    protected SpiderLogic $spiderLogic;

    public function __construct(App $app, SpiderLogic $spiderLogic)
    {
        parent::__construct($app);
        $this->spiderLogic = $spiderLogic;
    }

    /**
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index(): string
    {
        // 使用服务层获取蜘蛛统计数据
        $chartData = [
            'today_pie_chart' => $this->spiderLogic->getTodaySpiderPieChartData(),
            'yesterday_today_compare' => $this->spiderLogic->getYesterdayAndTodayCompare(),
            'seven_days_trend' => $this->spiderLogic->getRecentSevenDaysTrend(),
            'hourly_trend_by_spider' => $this->spiderLogic->getHourlyTrendTodayVsYesterdayBySpider(),
        ];
        // 传递给模板
        $this->assign([
            'chartData' => json_encode($chartData, JSON_UNESCAPED_UNICODE),
        ]);

        $this->assign(['logs_list' => $this->spiderLogic->getNewLogs()]);
        return $this->fetch();
    }

    public function add()
    {
        $this->error('不支持此功能');
    }

    public function edit(string $id = '')
    {
        $this->error('不支持此功能');
    }

    public function quickEdit($id): void
    {
        $this->error('不支持此功能');
    }
}
