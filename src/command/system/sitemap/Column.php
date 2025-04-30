<?php

namespace app\command\system\sitemap;

use app\logic\system\ModuleLogic;
use app\model\system\SystemSitemap;
use mowzs\lib\extend\SiteMapExtend;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;

class Column extends Command
{
    protected mixed $domain;
    /**
     * 查询条件 默认状态为1
     * @var array|int[]
     */
    protected array $where = ['status' => 1];
    /**
     * sitemap生成路径 返回路径
     * @var array|string[]
     */
    protected array $config;

    /**
     * 配置消息指令
     */
    protected function configure(): void
    {
        $this->setName('sitemap:column');
        $this->addArgument('type', Argument::OPTIONAL, '生成sitemap地图', 'xml');
        $this->addOption('domain', null, Option::VALUE_OPTIONAL, '生成sitamap域名 参数为pc 或者wap', 'pc');
        $this->setDescription('生成栏目sitemap网站地图');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     */
    protected function execute(Input $input, Output $output): void
    {

        $type = $input->getArgument('type');
        //参数处理
        $module = ModuleLogic::instance()->getSitemapModule();
        $domain = $input->getOption('domain');
        if ($domain == 'pc') {
            $this->domain = sys_config('site_domain');
        } else {
            $this->domain = sys_config('site_wap_domain', sys_config('site_domain'));
        }
        $this->setConfig($domain);
        $sitemap = new SiteMapExtend($this->config);
        foreach ($module as $key => $item) {
            if (sys_config($key . '.is_open_sitemap', 0)) {
                $table = "{$key}_column";
                $data = $this->app->db->name($table)->where($this->where)->field('id')->select();
                foreach ($data->toArray() as $value) {
//                    $url = "{$this->domain}/{$key}/list_{$value['id']}.html";
                    $url = $this->domain . urls("{$key}/column/index", ['id' => $value['id']]);
                    $sitemap->addItem($url, date('Y-m-d', time()));
                }
            }
        }
        $in_data = [
            'url' => $sitemap->generated($type, 'column'),
            'type' => $type,
            'module' => 'all',
            'class' => 'column',
            'domain' => $this->domain,
        ];
        SystemSitemap::where('type', $type)->where('class', 'column')->where('module', 'all')->delete();
        SystemSitemap::create($in_data);
        $this->output->info("生成栏目sitemap成功");

    }

    protected function setConfig($domain)
    {
        $this->config = [
            'path' => $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'sitemap' . DIRECTORY_SEPARATOR . 'column' . DIRECTORY_SEPARATOR,
            'pathurl' => $this->domain . '/sitemap/column/',
        ];
        if ($domain != 'pc') {
            $this->config = [
                'path' => $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'sitemap' . DIRECTORY_SEPARATOR . 'column_m' . DIRECTORY_SEPARATOR,
                'pathurl' => $this->domain . '/sitemap/column_m/',
            ];
        }
    }
}
