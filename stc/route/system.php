<?php

use think\facade\Route;

Route::rule('', 'index/index/index')->completeMatch();
Route::group('user', static function () {
    Route::rule('', 'user/index/index');
});
