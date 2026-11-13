<?php


namespace app\admin\system;

use think\App;
use app\common\traits\CrudTrait;
use app\logic\system\SpiderLogic;
use app\common\controllers\BaseAdmin;
use think\db\exception\DbException;
use think\db\exception\DataNotFoundException;
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
     * 蜘蛛统计看板
     *
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
            'charts'    => json_encode($dashboard['charts'], JSON_UNESCAPED_UNICODE),
        ]);
        
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
