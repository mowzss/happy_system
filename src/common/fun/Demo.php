<?php

namespace app\common\fun;

class Demo
{
    /**
     * @param $a
     * @param $b
     * @return string
     */
    public function get($a = '', $b = ''): string
    {
        return $a . $b;
    }
}
