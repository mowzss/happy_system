<?php

namespace app\common\command\push;

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
    protected string $jsonField = 'push->index_now';
    protected string $upJsonField = 'index_now';

    protected function configure(): void
    {
        $this->setName('push:indexnow');
        $this->addOption('module', null, Option::VALUE_REQUIRED, '模块名称');
        $this->addOption('num', null, Option::VALUE_OPTIONAL, '默认条数', 1000);
        $this->setDescription('生成sitemap网站地图');
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
        $this->contentPush($module, $num);
    }

    /**
     * 推送内容
     * @param string $module 模块
     * @param int $num
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     */
    private function contentPush(string $module, int $num): void
    {
        [$total, $count] = [
            (int)round(ceil($this->app->db->name($this->table)->json(['push'])
                    ->where($this->where)
                    ->where([$this->jsonField => 0])
                    ->whereOr([$this->jsonField => null])
                    ->setFieldType([$this->jsonField => 'int'])
                    ->count() / $num)),
            0];
        for ($i = 0; $i < $total; $i++) {
            $model = $this->app->db->name($this->table)->json(['push'])
                ->where($this->where)
                ->where([$this->jsonField => 0])
                ->whereOr([$this->jsonField => null])
                ->setFieldType([$this->jsonField => 'int'])
                ->field('id,url')->limit($num)->order('id', 'desc')->select()->toArray();
            $count++;
            $urls = [];
            foreach ($model as $value) {
                $urls[] = $this->domain . $value['url'];
            }
            $push = new BingIndexNowPusher($this->domain, sysconf('pusher.index_now_key'));
            try {
                $ret = $push->pushUrls($urls);
                if ($ret['code'] == 1) {
                    $this->queue->message($total, $count, "推送成功", 1);
                    foreach ($model as $key => $value) {
                        $up_data['push'][$this->upJsonField] = 1;
                        $this->app->db->name($this->table)
                            ->json(['push'])
                            ->where('id', $value['id'])
                            ->update($up_data);
                        $this->queue->message($total, $count, (count($model) - 1) . "/{$key}--正在更新数据库记录--[{$value['id']}]成功", 1);
                    }
                } else {
                    $this->queue->message($total, $count, "推送失败" . json_encode($ret), 1);
                }
            } catch (\Exception $e) {
                $this->queue->message($total, $count, $e->getMessage(), 1);
            }
        }

        $this->setQueueSuccess("本次共计推送 {$total}条数据。");
    }
}
