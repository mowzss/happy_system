<?php
declare(strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use mowzs\lib\helper\TemplateHelper;

class Theme extends BaseAdmin
{
    protected $info;

    protected array $types = [
        'home' => '网站',
        'admin' => '管理',
        'user' => '会员'
    ];

    /**
     * @auth true
     * @return string
     */
    public function index(): string
    {


        foreach ($this->types as $type => $name) {
            $this->info[$type] = TemplateHelper::instance()->getStyleInfo($type);
        }

        return $this->fetch();
    }
}
