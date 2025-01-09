<?php

namespace app\home\index;

use app\common\controllers\BaseHome;
use mowzs\lib\helper\QrcodeHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\facade\Request;
use think\Response;

// 用于处理 Logo

class Qrcode extends BaseHome
{
    /**
     * @param $info
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index($info = '')
    {
        $url = $this->request->param('url');
        if (empty($url)) {
            $url = Request::domain(true);
        }
        try {
            // 获取系统配置中的二维码 Logo
            $logoPath = (string)sys_config('qr_code_logo');
        } catch (DataNotFoundException|DbException $e) {
            $logoPath = '';
        }
        return Response::create(QrcodeHelper::getQrcode($url, $logoPath)->getString())->header(['Content-Type' => 'image/png']);
    }
}
