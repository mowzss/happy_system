<?php

namespace app\common\command\push;

use think\console\Command;
use think\console\input\Option;

class IndexNow extends Command
{
    protected function configure(): void
    {
        $this->setName('push:indexnow');
        $this->addOption('module', null, Option::VALUE_REQUIRED, '模块名称');
        $this->addOption('num', null, Option::VALUE_OPTIONAL, '默认条数', 10000);
        $this->setDescription('生成sitemap网站地图');
    }
}
