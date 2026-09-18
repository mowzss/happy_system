<?php
declare(strict_types=1);

namespace app\home\index;

use think\Response;
use think\facade\Request;
use app\common\controllers\BaseHome;
use happy\admin\libs\helper\QrcodeHelper;

// 用于处理 Logo

class Qrcode extends BaseHome
{
    /**
     * @return Response
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
