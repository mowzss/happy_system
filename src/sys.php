<?php

use think\Console;

Console::starting(function (Console $console) {
    $console->addCommands([
        \app\common\command\task\Sitemap::class,
    ]);
});
