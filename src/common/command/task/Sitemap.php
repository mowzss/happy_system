<?php

namespace app\common\command\task;

use app\model\system\SystemSitemap;
use mowzs\lib\helper\SiteMapHelper;
use think\Collection;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;

class Sitemap extends Command
{
    /**
     * @var int[]
     */
    protected array $where = ['status' => 1];
    /**
     * @var int[]
     */
    protected array $delWhere = ['status' => 0, 'delete_time' => null];
    /**
     * 域名
     * @var string
     */
    protected string $domain;
    /**
     * 数据表
     * @var string
     */
    protected string $table;
    /**
     * @var array|string[]
     */
    private array $config;

    /**
     * 配置消息指令
     */
    protected function configure(): void
    {
        $this->setName('task:sitemap');
        $this->addArgument('type', Argument::OPTIONAL, '生成sitemap地图', 'xml');
        $this->addOption('module', null, Option::VALUE_REQUIRED, '模块名称');
        $this->addOption('class', null, Option::VALUE_OPTIONAL, 'sitemap内容类型', 'content');
        $this->addOption('domain', null, Option::VALUE_OPTIONAL, '生成sitamap域名 参数为pc 或者wap', 'pc');
        $this->addOption('num', null, Option::VALUE_OPTIONAL, '默认条数', 10000);
        $this->setDescription('生成sitemap网站地图');
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
        $module = $input->getOption('module');
        if (empty($module)) {
            $output->error("没有指定模块");
        }
        if (!table_exists(strtolower($module) . '_content')) {
            $output->error('模块不存在');
        }
        $class = $input->getOption('class');
        $num = (int)$input->getOption('num');
        $domain = $input->getOption('domain');
        if ($domain == 'pc') {
            $this->domain = sys_config('site_domain');
        } else {
            $this->domain = sys_config('site_wap_domain', sys_config('site_domain'));
        }
        $this->setSitemap($module, $class, $domain);
        if ($class == 'content') {
            $this->buildContentMap($module, $type, $num, $class);
        }
        if ($class == 'cate') {
            $this->buildCateMap($module, $type, $class);
        }
        if ($class == 'tag') {
            $this->buildTagMap($module, $type, $num, $class);
        }
        if ($class == 'badlink') {
            $this->buildBadlinkMap($module, $type, $num, $class);
        }
    }

    /**
     * 设置生成信息
     * @param $module
     * @param $class
     * @param $domain
     * @return void
     */
    protected function setSitemap($module, $class, $domain): void
    {
        if ($class == "content" || $class == "badlink") {
            $this->table = strtolower($module) . '_content';
        } elseif ($class == 'tag') {
            $this->table = strtolower($module) . '_tag';
        } elseif ($class == 'cate') {
            $this->table = strtolower($module) . '_cate';
        }
        $this->config = [
            'path' => $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'sitemap' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR,
            'pathurl' => $this->domain . '/sitemap/' . $module . '/',
        ];
        if ($domain != 'pc') {
            $this->config = [
                'path' => $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'sitemap' . DIRECTORY_SEPARATOR . $module . '_m' . DIRECTORY_SEPARATOR,
                'pathurl' => $this->domain . '/sitemap/' . $module . '_m/',
            ];
        }
    }

    /**
     * 生成内容地图
     * @param string $module 模块
     * @param string $type sitemap类型 xml txt html
     * @param int $num 单个sitemap文件内容数量
     * @param string $class
     * @return void
     * @throws DbException
     * @throws Exception
     */
    private function buildContentMap(string $module, string $type = '', int $num = 10000, string $class = 'content'): void
    {
        [$total, $count] = [
            (int)ceil($this->app->db->name($this->table)->where($this->where)->count() / $num),
            0,
        ];
        $domain = $this->domain;
        $this->app->db->name($this->table)
            ->where($this->where)
            ->field('id,create_time')
            ->chunk($num, function (Collection $data) use (&$count, $total, $module, $type, $class, $domain) {
                $count++;
                $sitemap = new SiteMapHelper($this->config);
                foreach ($data->toArray() as $value) {
                    $url = $domain . urls($module . '/content/index', ['id' => $value['id']]);
                    $sitemap->addItem($url, format_datetime($value['create_time'], 'Y-m-d'));
                }
                $this->extracted($sitemap, $type, $class, $count, $module, $total);
            }, 'id', 'desc');
        $this->output->info("本次共计生成 {$total} 条sitemap。");
    }

    /**
     * 生成SiteMap
     * @param SiteMapHelper $sitemap
     * @param string $type
     * @param string $table
     * @param int $count
     * @param string $module
     * @param int $total
     * @return void
     * @throws DbException
     * @throws Exception
     */
    private function extracted(SiteMapHelper $sitemap, string $type, string $table, int $count, string $module, int $total): void
    {
        $in_data = [
            'url' => $sitemap->generated($type, $table . '_' . $count),
            'type' => $type,
            'module' => $module,
            'class' => $table,
            'domain' => $this->domain,
        ];
        if ($count == 1) {
            SystemSitemap::where('type', $type)->where('class', $table)->where(
                'domain',
                $this->domain
            )->where('module', $module)->delete();
        }
        SystemSitemap::create($in_data);
        $this->output->info("第[" . $count . "]条生成成功");
    }

    /**
     * 生成栏目地图
     * @param string $module 模块
     * @param string $type sitemap类型 xml txt html
     * @param string $class
     * @return void
     * @throws DbException
     * @throws Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    private function buildCateMap(string $module, string $type = '', string $class = 'cate'): void
    {
        $data = $this->app->db->name($this->table)->where($this->where)->field('url')->select();
        $sitemap = new SiteMapHelper($this->config);
        foreach ($data->toArray() as $value) {
            $url = $this->domain . $value['url'];
            $sitemap->addItem($url, date('Y-m-d', time()));
        }
        $in_data = [
            'url' => $sitemap->generated($type, $class),
            'type' => $type,
            'module' => $module,
            'class' => $class,
            'domain' => $this->domain,
        ];
        SystemSitemap::where('type', $type)->where('class', $class)->where('module', $module)->delete();
        SystemSitemap::create($in_data);
        $this->output->info("生成栏目sitemap成功");
    }

    /**
     * 生成TAG地图
     * @param string $module 模块
     * @param string $type sitemap类型 xml txt html
     * @param int $num 单个sitemap文件内容数量
     * @param string $class
     * @return void
     * @throws DbException
     * @throws Exception
     */
    private function buildTagMap(string $module, string $type = '', int $num = 10000, string $class = 'tag'): void
    {
        [
            $total,
            $count,
        ] = [(int)ceil($this->app->db->name($this->table)->field('id,url,create_time')->where($this->where)->count() / $num), 0];
        $this->app->db->name($this->table)->where($this->where)->chunk(
            $num,
            function (Collection $data) use (&$count, $total, $module, $type, $class) {
                $count++;
                $sitemap = new SiteMapHelper($this->config);
                foreach ($data->toArray() as $value) {
                    $url = $this->domain . $value['url'];
                    $sitemap->addItem($url, format_datetime($value['create_time'], 'Y-m-d'));
                }
                $this->extracted($sitemap, $type, $class, $count, $module, $total);
            },
            'create_time'
        );
        $this->output->info("本次共计生成 {$total} 条sitemap。");
    }

    /**
     * 生成死链地图
     * @param string $module 模块
     * @param string $type sitemap类型 xml txt html
     * @param int $num 单个sitemap文件内容数量
     * @param string $class
     * @return void
     * @throws DbException
     * @throws Exception
     */
    private function buildBadlinkMap(string $module, string $type = 'xml', int $num = 10000, string $class = 'badlink'): void
    {
        [$total, $count] = [(int)ceil($this->app->db->name($this->table)->whereOr($this->delWhere)->count() / $num), 0];
        $data = $this->app->db->name($this->table)->field('id,url,create_time')->whereOr($this->delWhere)->field('id,create_time')->select()->toArray();
        while ($total > $count) {
            $count++;
            $sitemap = new SiteMapHelper($this->config);
            foreach ($data as $value) {
                $url = $this->domain . $value['url'];
                $sitemap->addItem($url, format_datetime($value['create_time'], 'Y-m-d H:i:s'));
            }
            $this->extracted($sitemap, $type, $class, $count, $module, $total);
        }
        $this->output->info("本次共计生成 {$total} 条sitemap。");
    }
}
