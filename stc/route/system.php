<?php

\think\facade\Route::group('user', function () {
    \think\facade\Route::rule('', 'index/index');
});
