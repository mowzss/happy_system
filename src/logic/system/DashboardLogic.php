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
     * 完全不缓存。dashboard 各数据源本身已有缓存（SpiderLogic 查询缓存、
     * 表元数据 schema 缓存等），DashboardLogic 自身再包一层缓存容易导致：
     * 1) 某次请求 SpiderLogic 异常 → 空 spider 数据被缓存 → 后续整分钟全坏
     * 2) 菜单/模块数变化后需要等缓存过期才生效
     * 单次请求开销可忽略，直接实时组装。
     *
     * @return array
     */
    public function build(): array
    {
        return [
            'overview'    => $this->buildOverview(),
            'content'     => $this->buildContentStats(),
            'system'      => $this->buildSystemInfo(),
            'php_ext'     => $this->buildPhpExtensions(),
            'quick_links' => $this->buildQuickLinks(),
            'spider'      => $this->buildSpider(),
            'composer'    => $this->buildComposer(),
            'is_debug'    => $this->app->isDebug(),
        ];
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
                'icon'  => $this->resolveMenuIcon($row['icon'] ?? '', $node, $row['title'] ?? ''),
                'url'   => aurl($node),
            ];
        }

        return $items;
    }

    /**
     * 菜单 icon 兜底：数据库 icon 为空时，根据 node 前缀/标题语义给一个 layui-icon
     *
     * @param string $dbIcon  数据库原始 icon 值
     * @param string $node    节点字符串，如 system/attachment/index
     * @param string $title   菜单标题
     * @return string
     */
    protected function resolveMenuIcon(string $dbIcon, string $node, string $title): string
    {
        if (trim($dbIcon) !== '') {
            return $dbIcon;
        }

        // 根据 node 路径关键词猜图标
        $node = strtolower($node);
        $rules = [
            '/user/'           => 'layui-icon layui-icon-username',
            '/attachment/'     => 'layui-icon layui-icon-folder',
            '/spider/'         => 'layui-icon layui-icon-bot',
            '/operationlog|log/' => 'layui-icon layui-icon-log',
            '/sitemap/'        => 'layui-icon layui-icon-website',
            '/task/'           => 'layui-icon layui-icon-time',
            '/feedback/'       => 'layui-icon layui-icon-about',
            '/links/'          => 'layui-icon layui-icon-link',
            '/config/'         => 'layui-icon layui-icon-set',
            '/setting/'        => 'layui-icon layui-icon-set',
            '/module/'         => 'layui-icon layui-icon-component',
            '/menu/'           => 'layui-icon layui-icon-list',
            '/nav/'            => 'layui-icon layui-icon-website',
            '/event/'          => 'layui-icon layui-icon-rate-half',
            '/indexnow/'       => 'layui-icon layui-icon-release',
            '/group/'          => 'layui-icon layui-icon-group',
        ];
        foreach ($rules as $pattern => $icon) {
            if (preg_match($pattern, $node)) {
                return $icon;
            }
        }

        // 根据菜单标题关键词兜底
        $titleRules = [
            '用户'  => 'layui-icon layui-icon-username',
            '文件'  => 'layui-icon layui-icon-folder',
            '蜘蛛'  => 'layui-icon layui-icon-bot',
            '日志'  => 'layui-icon layui-icon-log',
            '设置'  => 'layui-icon layui-icon-set',
            '模块'  => 'layui-icon layui-icon-component',
            '菜单'  => 'layui-icon layui-icon-list',
            '事件'  => 'layui-icon layui-icon-rate-half',
            '链接'  => 'layui-icon layui-icon-link',
            '反馈'  => 'layui-icon layui-icon-about',
            '导航'  => 'layui-icon layui-icon-website',
            '任务'  => 'layui-icon layui-icon-time',
        ];
        foreach ($titleRules as $keyword => $icon) {
            if (str_contains($title, $keyword)) {
                return $icon;
            }
        }

        // 最终兜底
        return 'layui-icon layui-icon-console';
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
