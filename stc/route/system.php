<?php

use think\facade\Route;

Route::group('user', static function () {
    Route::rule('', 'user/index/index');
});
