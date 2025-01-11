<?php

return [
    //登录页面
    'auth_login' => 'index/login/index',
    //超管账号 全权限 不做限制
    'auth_admin' => '{USERNAME}',
    //后台入口文件
    'admin_entrance' => "admin.php",
    //系统安装状态 true 状态锁定安装 非必要不要更改
    'installed' => true,
    // 32位加密密钥 用于为\mowzs\lib\helper\CryptoHelper::aesEncrypt()提供默认密钥 可自行修改
    'default_encryption_key' => 'thisisaverysecretkey!thisisaverysecretkey!'
];
