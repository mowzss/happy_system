<?php
declare (strict_types=1);

namespace app\model\user;

use mowzs\lib\Model;

class UserAuth extends Model
{
    // 设置JSON数据返回数组
    protected function getOptions(): array
    {
        return [
            'type' => [
                // 设置JSON字段的类型
                'nodes' => 'json'
            ],
            'jsonAssoc' => true,  // 设置JSON数据返回数组
        ];
    }
}
