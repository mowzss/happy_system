<?php
declare (strict_types=1);


namespace app\api\controller;

use app\common\traits\UploadTraits;
use app\common\controllers\BaseApi;

class Upload extends BaseApi
{
    use UploadTraits;
    
    /**
     * 保存文件
     * @login true
     * @return void
     */
    public function save(): void
    {
        // 获取表单上传文件
        $file = $this->app->request->file('file');
        
        if (empty($file)) {
            $this->json([], 400, '请选择要上传的文件');
        }
        
        try {
            $data = $this->storeUploadedFile($file);
            $this->json($data);
        } catch (\Throwable $e) {
            $this->json([], 400, $e->getMessage());
        }
    }
}
