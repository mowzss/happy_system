<?php
declare(strict_types=1);

namespace app\home\index;

use think\Response;
use think\facade\Request;
use mowzs\lib\helper\QrcodeHelper;
use think\db\exception\DbException;
use app\common\controllers\BaseHome;
use think\db\exception\DataNotFoundException;

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
    public function index(): Response
    {
        $url = $this->request->param('url');
        if (empty($url)) {
            $url = Request::domain(true);
        }
        $logoPath = '';
        if (!empty($this->web_config['system']['square_logo'])) {
            $logoPath = $this->web_config['system']['square_logo'];
        }
        return Response::create(QrcodeHelper::getQrcode($url, $logoPath)->getString())->header(['Content-Type' => 'image/png']);
    }
    
}
