<?php

use think\facade\Route;

Route::get('', 'index/index/index');
Route::group('user', static function () {
    Route::get('', 'user.index/index');
});
Route::get('static/:path', function (string $path) {
    $filename = public_path() . $path;
    return new \think\worker\response\File($filename);
})->pattern(['path' => '.*\.\w+$']);
