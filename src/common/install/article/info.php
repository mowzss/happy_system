<?php
return [
    'name' => '文章模块',
    'info' => '模块简介',
    'version' => '1.0.0',
    'is_copy' => true,
    'install_files' => [
        'install.sql',
        app\common\install\article\Install::class,
    ],
    'keyword' => 'article'
];
