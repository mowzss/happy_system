<?php

declare (strict_types=1);

namespace app\home\index;

use app\common\controllers\BaseHome;
use mowzs\lib\module\service\ContentBaseService;

class Index extends BaseHome
{
    /**
     * @return string
     */
    public function index()
    {


        return $this->fetch();
    }

    public function text()
    {
        $array = [
            "pagenum" => NULL,
            "rows" => 10,
            "paginate" => 0,
            "where" => [
                [
                    "view",
                    "<",
                    "800"
                ],
                [
                    "is_pic",
                    "=",
                    "1"
                ],
                [
                    "status",
                    "=",
                    1
                ],
                [
                    "cid",
                    "in",
                    [11]
                ]
            ],
            "by" => "desc",
            "order" => "view",
        ];
        ContentBaseService::instance(['article'])->getList($array);
        echo '';
    }
}
