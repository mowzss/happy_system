<?php

use think\facade\Route;

Route::get('', 'index/index/index');
Route::redirect('index', '/');
Route::redirect('index/index', '/');

Route::get('user', 'user/index/index');
