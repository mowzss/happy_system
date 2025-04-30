<?php

namespace app\command\system\indexnow;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class IndexNowPush extends Command
{

    /**
     * @var int[]
     */
    protected array $where = ['status' => 1, 'delete_time' => null];
    /**
     * 域名
     * @var string
     */
    protected string $domain;
    /**
     * @var string
     */
    protected string $jsonField = 'extend->index_now';
    /**
     * @var string
     */
    protected string $upJsonField = 'index_now';

    protected function configure(): void
    {
        $this->setName('indexnow:push');
        $this->addArgument('module', Argument::OPTIONAL, '模块标记', 'article');
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
        $module = $input->getArgument('module');
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
        $push = new \mowzs\lib\extend\push\IndexNowPush($this->domain, sys_config('p_index_now.index_key'));
        $model_table = $module . '_model';
        $models = $this->app->db->name($model_table)->where('id', '>', 0)->column('title', 'id');
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
        $urls = [];
        foreach ($data as $value) {
            $urls[] = $this->domain . urls($module . '/content/index', ['id' => $value['id']]);
        }
        return $urls;
    }
}
