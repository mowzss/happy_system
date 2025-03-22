<?php

namespace app\common\command\push;

use mowzs\lib\extend\push\IndexNowPush;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class IndexNow extends Command
{

    /**
     * @var int[]
     */
    protected array $where = ['status' => 1, 'deleted_time' => null];
    /**
     * 域名
     * @var string
     */
    protected string $domain;
    protected string $jsonField = 'extend->index_now';
    protected string $upJsonField = 'index_now';


    protected function configure(): void
    {
        $this->setName('push:indexnow');
        $this->addOption('module', null, Option::VALUE_REQUIRED, '模块名称');
        $this->addOption('num', null, Option::VALUE_OPTIONAL, '默认条数', 1000);
        $this->addOption('domain', null, Option::VALUE_OPTIONAL, 'bing indexNow 域名 参数为pc 或者wap', 'pc');
        $this->setDescription('IndexNow推送');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function execute(Input $input, Output $output): void
    {
        //参数处理
        $module = $input->getOption('module');
        if (empty($module)) {
            $output->error("没有指定模块");
        }
        $num = (int)$input->getOption('num');
        $domain = $input->getOption('domain');
        if ($domain == 'pc') {
            $this->domain = sys_config('site_domain');
        } else {
            $this->domain = sys_config('site_wap_domain', sys_config('site_domain'));
            $this->jsonField = 'extend->m_index_now';
            $this->upJsonField = 'm_index_now';
        }
        $this->contentPush($module, $num);
    }

    /**
     * 推送内容
     * @param string $module 模块
     * @param int $num
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function contentPush(string $module, int $num): void
    {
        $push = new IndexNowPush($this->domain, sys_config('p_index_now.index_key'));
        $model_table = $module . '_model';
        $models = $this->app->db->name($model_table)->where('id', '>', 0)->where('deleted_time', null)->column('title', 'id');
        foreach ($models as $mid => $model_name) {
            $content_table = $module . '_content_' . $mid;
            $this->app->db->name($content_table)->json(['extend'])->where($this->where)->where(function ($query) {
                $query->where($this->jsonField, 0)->whereOr($this->jsonField, null);
            })->field('id')->chunk($num, function ($data) use ($module, $push, $content_table) {
                $urls = $this->createContentUrl($module, $data);
                $ret = $push->pushUrls($urls);
                if ($ret['code'] == 1) {
                    $this->output->info("推送成功");
                    $this->updateContent($data, $content_table);
                } else {
                    $this->output->error("推送失败" . json_encode($ret));
                }
            });
        }
        $this->output->info('推送完成');
    }

    /**
     * 更新记录
     * @param $data
     * @param string $content_table
     * @return void
     * @throws DbException
     */
    protected function updateContent($data, string $content_table): void
    {
        foreach ($data as $key => $value) {
            $up_data['extend'][$this->upJsonField] = 1;
            $this->app->db->name($content_table)
                ->json(['extend'])
                ->where('id', $value['id'])
                ->update($up_data);
            $this->output->info("正在更新数据库记录--[{$value['id']}]成功");
        }
    }

    /**
     * 创建内容链接
     * @param string $module
     * @param $data
     * @return array|string[]
     */

    private function createContentUrl(string $module, $data)
    {
        return array_map(function ($value) use ($module) {
            return $this->domain . urls($module . '/content/index', ['id' => $value['id']]);
        }, $data);
    }
}
