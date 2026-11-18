<?php

use think\facade\Route;

Route::redirect('index$', '/');
Route::redirect('index/$', '/');
Route::redirect('index/index$', '/');
Route::redirect('index/index/$', '/');

Route::get('$', 'index/index/index');
Route::get('user$', 'user/index/index');
