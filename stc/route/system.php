<?php

use think\facade\Route;

Route::redirect('index', '/');

Route::get('', 'index/index/index');
