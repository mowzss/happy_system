<?php
// API 路由：直连 cms-core 通用控制器，无需为每个频道建 Index 薄壳。
//
// 注意：本文件会被 php think optimize:route 扫描并生成 runtime/route.php 缓存。
// 路由缓存是 var_export 出的纯数据，文件中的 if/条件判断在生成时被固化，
// 因此这里必须无条件注册路由，不可使用 env('CONTROLLER_LAYER') 之类的运行时判断。
//
// 本文件路径只在 api 入口（/api.php/...）被请求，与前台 /cms/data 入口隔离，
// 即使 home 入口加载了这条规则也不会被触发，无副作用。

use think\facade\Route;

// 通用内容查询（对齐 {hp:lists} 参数）：/api.php/cms/data?module=dict&mid=1&rows=8
Route::get('cms/data', '\mowzs\cms\controller\api\Index@data');

// 搜索 API：直连完整类名。
// 注意：不能用 search/* 前缀——home 层 route/search.php 有 Route::get('search$', ...)，
// 其 'search' 规则会抢占前缀并把 /search/index/index 误解析为 app\api\Search。
// 故统一归入 cms/ 前缀（与 cms/data 一致）。
// 更具体的规则放前面；基础搜索规则加 $ 完全匹配，避免前缀抢占子路径
Route::get('cms/search/hotWord', '\app\api\search\Index@hotWord');
Route::get('cms/search/modules', '\app\api\search\Index@modules');
Route::get('cms/search$', '\app\api\search\Index@index');
