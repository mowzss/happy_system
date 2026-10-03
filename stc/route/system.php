<?php

use think\facade\Route;

Route::get('', 'index/index/index');
Route::redirect('index', '/');
Route::redirect('index/index', '/');
Route::group('user', static function () {
    Route::get('', 'user.index/index');
});
