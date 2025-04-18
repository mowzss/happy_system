<?php
declare(strict_types=1);

namespace app\logic\system;

use app\model\system\SystemNav;
use mowzs\lib\BaseLogic;
use mowzs\lib\helper\DataHelper;

class NavLogic extends BaseLogic
{
    protected function initialize(): void
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * @param string $dir
     * @return mixed
     * @throws \Throwable
     */
    public function getNavByDir(string $dir = 'pc'): mixed
    {
        return $this->app->cache->remember('site_nav_' . $dir, function () use ($dir) {
            $where = ['dir' => $dir, 'status' => 1];
            $nav_data = SystemNav::where($where)->order('list', 'desc')->select()->each(function ($item) {
                if (empty($item['url']) && !empty($item['node'])) {
                    $item['url'] = hurl($item['node'], $item['params'] ?: []);
                }
                return $item;
            })->toArray();
            return DataHelper::instance()->arrToTree($nav_data, 0, 'id', 'pid', 'sub');
        }, 3600);
    }
}
