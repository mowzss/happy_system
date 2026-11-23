<?php


namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\logic\system\SpiderLogic;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class Spider extends BaseAdmin
{

    protected SpiderLogic $spiderLogic;

    public function __construct(App $app, SpiderLogic $spiderLogic)
    {
        parent::__construct($app);
        $this->spiderLogic = $spiderLogic;
    }

    /**
     * 蜘蛛统计看板
     * @auth true
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index(): string
    {
        $dashboard = $this->spiderLogic->getDashboard();

        $this->assign([
            'dashboard' => $dashboard,
            'charts' => json_encode($dashboard['charts'], JSON_UNESCAPED_UNICODE),
        ]);

        return $this->fetch();
    }
}
