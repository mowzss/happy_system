<?php

use think\facade\Route;

Route::rule('', 'index/index/index');
Route::group('user', static function () {
    Route::rule('', 'user.index/index');
});
