<?php

use think\facade\Route;

Route::group('article', function () {
    Route::rule(':id', 'article/content/index');
    Route::rule('list-:id', 'article/column/index');
    Route::rule('', 'article/index/index');
})->pattern(['id' => '\d+', 'name' => '\w+']);

Route::auto();
