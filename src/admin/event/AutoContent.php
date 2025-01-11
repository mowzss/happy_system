<?php

namespace app\admin\event;

use mowzs\lib\event\controller\admin\SettingAdmin;

class AutoContent extends SettingAdmin
{
    protected string $config_module = 'auto_content';

    protected string $title = '发布内容处理';
}
