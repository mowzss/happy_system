<?php

namespace app\home\index;

use app\common\controllers\BaseHome;

class Jumps extends BaseHome
{
    /**
     * 异步跳转接口
     * @return void
     */
    public function ajax(): void
    {
        if (!$this->request->isAjax()) {
            $this->error('请求方法错误');
        }
        $url = $this->request->post('url');
        if (empty($url)) {
            $this->error('参数错误');
        }
        $vars = $this->request->post('vars/a');
        if (!empty($vars) && !is_array($vars)) {
            $this->error('参数错误');
        }
        $this->success('ok', urls($url, $vars));
    }
}
