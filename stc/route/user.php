<?php

use think\facade\Route;

Route::group('user', function () {
    Route::get('$', 'user/index/index');
});
