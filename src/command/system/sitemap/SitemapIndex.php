<?php

namespace app\command\system\sitemap;

use app\model\system\SystemSitemap;
use mowzs\lib\extend\RuntimeExtend;
use mowzs\lib\extend\SitemapIndexExtend;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

class SitemapIndex extends Command
{
    protected mixed $domain;

    protected function configure(): void
    {
        $this->setName('sitemap:index');
        $this->addOption('domain', null, Option::VALUE_OPTIONAL, '生成sitamap域名 参数为pc 或者wap', 'pc');
        $this->setDescription('生成sitemap索引文件');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function execute(Input $input, Output $output): void
    {
        if (!RuntimeExtend::checkRoute()) {
            Log::error('当前命令【sitemap:index】可执行条件不足-Route');
            $output->error('当前命令【sitemap:index】可执行条件不足-Route');
            return;
        }
        $domain = $input->getOption('domain');
        if ($domain == 'pc') {
            $this->domain = sys_config('site_domain');
        } else {
            $this->domain = sys_config('site_wap_domain', sys_config('site_domain'));
        }
        $url_path = $this->domain . DIRECTORY_SEPARATOR . 'sitemap' . DIRECTORY_SEPARATOR . 'sitemap_index.xml';
        $file_path = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'sitemap' . DIRECTORY_SEPARATOR . 'sitemap_index.xml';
        $data = SystemSitemap::where('type', 'xml')->select()->toArray();
        $sitemap_index = new SitemapIndexExtend();
        foreach ($data as $item) {
            $sitemap_index->addSitemap($item['url'], $item['create_time']);
        }
        $sitemap_index->saveToFile($file_path);
        $in_data = [
            'url' => $url_path,
            'type' => 'index_xml',
            'module' => 'all',
            'class' => 'sitemap',
            'domain' => $this->domain,
        ];
        SystemSitemap::where('type', 'index_xml')->where('class', 'sitemap')->where('module', 'all')->delete();
        SystemSitemap::create($in_data);
        $output->info('生成sitemap索引文件成功');
    }
}
