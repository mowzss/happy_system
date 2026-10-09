<?php
declare(strict_types=1);

namespace app\command\system\sitemap;

use think\facade\Log;
use think\console\Input;
use think\console\Output;
use think\console\Command;
use think\console\input\Option;
use app\model\system\SystemSitemap;
use happy\admin\libs\extend\RuntimeExtend;
use happy\admin\libs\extend\SitemapIndexExtend;

class SitemapIndex extends Command
{
    protected string $domain;
    
    protected function configure(): void
    {
        $this->setName('sitemap:index');
        $this->addOption('domain', null, Option::VALUE_OPTIONAL, '域名类型: pc 或 wap', 'pc');
        $this->setDescription('生成sitemap索引文件');
    }
    
    /**
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output): void
    {
        if (!RuntimeExtend::checkRoute()) {
            $msg = '当前命令【sitemap:index】可执行条件不足-Route';
            Log::error($msg);
            $output->error($msg);
            return;
        }
        
        $domainType = $input->getOption('domain') ?: 'pc';
        $this->domain = ($domainType === 'pc')
            ? sys_config('site_domain')
            : sys_config('site_wap_domain', sys_config('site_domain'));
        
        // 【修复】按当前域名精确过滤，避免PC/WAP子地图混杂
        $data = SystemSitemap::where('type', 'xml')
            ->where('domain', $this->domain)
            ->select()
            ->toArray();
        
        if (empty($data)) {
            $output->warning('⚠️ 未找到任何 xml 类型的子地图记录，跳过索引生成');
            return;
        }
        
        // 【优化】前置清理旧索引记录，保证原子性
        SystemSitemap::where('type', 'index_xml')
            ->where('class', 'sitemap')
            ->where('module', 'all')
            ->where('domain', $this->domain)
            ->delete();
        
        // 构建索引文件
        $sitemapIndex = new SitemapIndexExtend();
        foreach ($data as $item) {
            $lastmod = !empty($item['create_time'])
                ? format_datetime($item['create_time'], 'Y-m-d')
                : date('Y-m-d');
            $sitemapIndex->addSitemap((string)$item['url'], $lastmod);
        }
        
        // 【修复】URL 用 / 拼接，物理路径用 DIRECTORY_SEPARATOR
        $fileDir = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'sitemap' . DIRECTORY_SEPARATOR;
        $fileName = 'sitemap_index.xml';
        $filePath = $fileDir . $fileName;
        $urlPath = rtrim($this->domain, '/') . '/sitemap/' . $fileName;
        
        $sitemapIndex->saveToFile($filePath);
        
        // 写入数据库
        SystemSitemap::create([
            'url' => $urlPath,
            'type' => 'index_xml',
            'module' => 'all',
            'class' => 'sitemap',
            'domain' => $this->domain,
        ]);
        
        $output->info("🎉 Sitemap 索引生成成功: " . count($data) . " 个子地图");
    }
}
