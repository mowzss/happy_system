<?php
declare (strict_types=1);

namespace app\model\user;

use mowzs\lib\Model;

class UserAuth extends Model
{
    // 设置json类型字段
    protected $json = ['nodes'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;
}
