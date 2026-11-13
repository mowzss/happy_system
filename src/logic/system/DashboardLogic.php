<?php
declare (strict_types=1);

namespace app\logic\system;

use happy\admin\libs\BaseLogic;
use app\model\system\SystemMenu;
use app\model\system\SystemModule;
use app\model\system\SystemNav;
use happy\admin\libs\helper\ComposerHelper;
use think\facade\Db;

/**
 * 后台首页（欢迎页）数据组装逻辑
 *
 * 将 Index::main() 控制器中的数据准备工作全部下沉至此，
 * 控制器仅负责 $this->fetch()，DashboardLogic 负责所有统计查询与数据结构组装。
 */
class DashboardLogic extends BaseLogic
{
    /**
     * 看板缓存秒数（系统统计类数据允许短缓存，避免多次请求重复扫描 information_schema）
     */
    protected const CACHE_TTL = 60;

    /**
     * 内容模块目录 => 中文展示名
     * （与 ModuleLogic::getModuleAll() 保持一致，这里仅用于看板分组统计）
     */
    protected const CONTENT_MODULES = [
        'article'  => '文章',
        'astro'    => '星座',
        'calendar' => '日历',
        'chaxun'   => '查询',
        'jiemeng'  => '解梦',
        'poetry'   => '诗词',
        'mingzi'   => '名字',
        'shengri'  => '生日',
        'shengxiao'=> '生肖',
        'lingqian' => '灵签',
        'tools'    => '工具',
    ];

    /**
     * 组装后台首页完整数据
     *
     * @return array
     */
    public function build(): array
    {
        $cacheKey = 'admin_dashboard_' . date('YmdHi');
        $cached = $this->app->cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $data = [
            'overview'    => $this->buildOverview(),
            'content'     => $this->buildContentStats(),
            'system'      => $this->buildSystemInfo(),
            'php_ext'     => $this->buildPhpExtensions(),
            'quick_links' => $this->buildQuickLinks(),
            'spider'      => $this->buildSpider(),
            'composer'    => $this->buildComposer(),
            'is_debug'    => $this->app->isDebug(),
        ];

        $this->app->cache->set($cacheKey, $data, self::CACHE_TTL);

        return $data;
    }

    /**
     * 顶部概览卡片数据（总内容/模块/蜘蛛/附件）
     *
     * @return array
     */
    protected function buildOverview(): array
    {
        $moduleCount = SystemModule::where('status', 1)->count();
        $menuCount   = SystemMenu::where('status', 1)->count();
        $navCount    = SystemNav::where('status', 1)->count();

        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd   = $todayStart + 86400;

        try {
            $todaySpider = (int)Db::name('system_spider_logs')
                ->where('create_time', '>=', $todayStart)
                ->where('create_time', '<', $todayEnd)
                ->count();
        } catch (\Throwable) {
            $todaySpider = 0;
        }

        try {
            $todayUnique = (int)Db::query(
                "SELECT COUNT(DISTINCT url) AS c FROM xz_system_spider_logs WHERE create_time >= {$todayStart} AND create_time < {$todayEnd}"
            )[0]['c'];
        } catch (\Throwable) {
            $todayUnique = 0;
        }

        try {
            $attachment = (int)Db::name('system_attachment')->count();
        } catch (\Throwable) {
            $attachment = 0;
        }

        $contentTotal = 0;
        foreach ($this->moduleContentCounts() as $row) {
            $contentTotal += (int)($row['total'] ?? 0);
        }

        return [
            'content_total'        => number_format($contentTotal),
            'content_total_int'    => $contentTotal,
            'module_count'         => number_format($moduleCount),
            'menu_count'           => number_format($menuCount),
            'nav_count'            => number_format($navCount),
            'today_spider'         => number_format($todaySpider),
            'today_spider_int'     => $todaySpider,
            'today_unique'         => number_format($todayUnique),
            'today_unique_int'     => $todayUnique,
            'attachment'           => number_format($attachment),
        ];
    }

    /**
     * 各模块内容数量（用于看板列表 / 迷你柱状图）
     *
     * @return array
     */
    protected function buildContentStats(): array
    {
        $rows = $this->moduleContentCounts();
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'module' => $row['module'],
                'label'  => self::CONTENT_MODULES[$row['module']] ?? ucfirst($row['module']),
                'total'  => (int)($row['total'] ?? 0),
                'text'   => number_format((int)($row['total'] ?? 0)),
            ];
        }

        // 按数量降序
        usort($items, static fn (array $a, array $b) => $b['total'] <=> $a['total']);

        // 计算最大值用于前端条形图渲染
        $max = 0;
        foreach ($items as $item) {
            if ($item['total'] > $max) {
                $max = $item['total'];
            }
        }

        return [
            'items' => $items,
            'max'   => $max,
            'total' => array_sum(array_column($items, 'total')),
        ];
    }

    /**
     * 扫描 information_schema 统计各模块的 content 系列表行数
     *
     * @return array
     */
    protected function moduleContentCounts(): array
    {
        try {
            $prefix = (string)$this->app->db->connect()->getConfig('prefix');
            $schema = (string)$this->app->db->connect()->getConfig('database');
        } catch (\Throwable) {
            return [];
        }
        if ($prefix === '' || $schema === '') {
            return [];
        }

        $rows = Db::query(
            "SELECT TABLE_NAME, IFNULL(TABLE_ROWS,0) AS total
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = '{$schema}'
               AND TABLE_NAME REGEXP '^{$prefix}[a-z_]+_content_?[0-9]*$'"
        );

        $pattern = '/^' . preg_quote($prefix, '/') . '([a-z0-9_]+)_content/';
        $map = [];
        foreach ($rows as $row) {
            if (!preg_match($pattern, $row['TABLE_NAME'], $m)) {
                continue;
            }
            $mod = $m[1];
            // 仅统计声明过的内容模块（避免把 system_* / user_* 之类误算进去）
            if (!isset(self::CONTENT_MODULES[$mod])) {
                continue;
            }
            $map[$mod] = ($map[$mod] ?? 0) + (int)$row['total'];
        }

        $result = [];
        foreach (self::CONTENT_MODULES as $mod => $label) {
            if (($map[$mod] ?? 0) > 0) {
                $result[] = ['module' => $mod, 'label' => $label, 'total' => $map[$mod]];
            }
        }

        return $result;
    }

    /**
     * 系统基础信息
     *
     * @return array
     */
    protected function buildSystemInfo(): array
    {
        $mysqlVersion = '—';
        try {
            $mysqlVersion = Db::query("SELECT version() AS v")[0]['v'] ?? '—';
        } catch (\Throwable) {
        }

        return [
            'info'          => php_uname(),
            'ip'            => $this->request->server('server_addr', $this->request->server('local_addr', getHostByName(getHostName()))),
            'php_version'   => PHP_VERSION,
            'mysql_version' => $mysqlVersion,
            'php_run_memory'=> round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'php_sapi'      => php_sapi_name(),
            'php_os'        => PHP_OS,
            'think_version' => $this->app->version(),
            'server_time'   => date('Y-m-d H:i:s'),
            'timezone'      => date_default_timezone_get(),
        ];
    }

    /**
     * 常用 PHP 扩展检测
     *
     * @return array
     */
    protected function buildPhpExtensions(): array
    {
        $extensions = [
            'pdo'      => 'PDO',
            'pdo_mysql'=> 'PDO MySQL',
            'curl'     => 'cURL',
            'fileinfo' => 'Fileinfo',
            'openssl'  => 'OpenSSL',
            'gd'       => 'GD',
            'mbstring' => 'Multibyte',
            'redis'    => 'Redis',
            'imagick'  => 'ImageMagick',
        ];

        $rows = [];
        foreach ($extensions as $name => $label) {
            $loaded = extension_loaded($name);
            $rows[$name] = [
                'label' => $label,
                'name'  => $name,
                'loaded'=> $loaded,
                'text'  => $loaded ? '已安装' : '未安装',
                'class' => $loaded ? 'ok' : 'bad',
            ];
        }

        return $rows;
    }

    /**
     * 从菜单表中抽取快捷入口（有 node 的非分组节点，按 sort 取前 8 条）
     *
     * @return array
     */
    protected function buildQuickLinks(): array
    {
        $rows = SystemMenu::where('status', 1)
            ->where('class', 1)
            ->where('node', '<>', '#')
            ->order('list', 'asc')
            ->order('id', 'asc')
            ->field('title,node,icon')
            ->limit(10)
            ->select()
            ->toArray();

        $items = [];
        foreach ($rows as $row) {
            $node = trim($row['node'] ?? '');
            if ($node === '') {
                continue;
            }
            $items[] = [
                'title' => $row['title'],
                'node'  => $node,
                'icon'  => $row['icon'] ?? 'layui-icon layui-icon-console',
                'url'   => aurl($node),
            ];
        }

        return $items;
    }

    /**
     * 蜘蛛看板（复用 SpiderLogic 现成能力，这里只取看板主数据 + 近 7 天折线）
     *
     * @return array
     */
    protected function buildSpider(): array
    {
        try {
            $dashboard = SpiderLogic::instance()->getDashboard(days: 7, logRows: 8);
        } catch (\Throwable) {
            $dashboard = [];
        }

        return [
            'status'   => $dashboard['status'] ?? [],
            'overview' => $dashboard['overview'] ?? [],
            'ranking'  => array_slice($dashboard['ranking'] ?? [], 0, 5),
            'logs'     => $dashboard['logs'] ?? [],
            'trend'    => [
                'labels' => $dashboard['charts']['trend']['dates'] ?? [],
                'series' => $dashboard['charts']['trend']['series'] ?? [],
            ],
        ];
    }

    /**
     * Composer 依赖（保留原有逻辑，包异常时优雅降级为空）
     *
     * @return array
     */
    protected function buildComposer(): array
    {
        $packages = ['happy_modules' => [], 'system_modules' => []];
        try {
            $packages['happy_modules'] = ComposerHelper::getPackagesByType();
            $packages['system_modules'] = ComposerHelper::getPackagesExceptType();
        } catch (\Throwable) {
        }

        return $packages;
    }
}
