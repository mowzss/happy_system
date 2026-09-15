<?php

use think\Paginator;
use think\exception\Handle;

return [
    Paginator::class => \happy\admin\libs\Page::class,
    Handle::class => \app\ExceptionHandle::class,
];
