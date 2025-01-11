<?php

namespace app\common\event;

class AutoContent
{
    /**
     * 自动下载远程图片 提取缩略图
     * @param $data
     * @return void
     */
    public function handle($data)
    {
        if (!empty($data['content'])) {
            $content = $data['content'];


        }
    }
}
