<?php
declare (strict_types=1);

namespace app\api\index;

use app\common\controllers\BaseApi;
use app\common\traits\UploadTraits;
use app\model\system\SystemAttachment;
use think\facade\Filesystem;

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

        // 验证文件
        try {
            $this->validateFile($file);
        } catch (\Exception $e) {
            $this->json([], 400, $e->getMessage());
        }

        // 使用Filesystem facade 处理文件上传
        $disk = Filesystem::disk($this->storage_driver); // 可以根据配置切换到其他存储驱动
        $filename = $file->getOriginalName();
        $path = $disk->putFile('/', $file, 'md5');

        if ($path) {
            // 成功上传后 获取上传信息
            $data = [
                'uid' => $this->uid, // 假设用户ID存储在session中
                'name' => $filename,
                'path' => $path,
                'url' => Filesystem::disk($this->storage_driver)->url($path), // 获取文件访问链接
                'mime' => $file->getOriginalMime(),
                'ext' => pathinfo($filename, PATHINFO_EXTENSION),
                'size' => $file->getSize(),
                'md5' => md5_file($file->getPathname()),
                'sha1' => sha1_file($file->getPathname()),
                'driver' => 'local', // 默认本地驱动
                'create_time' => time(),
                'update_time' => time(),
            ];

            // 如果是图片文件，获取宽度和高度
            if (in_array($data['mime'], ['image/jpeg', 'image/png', 'image/gif'])) {
                list($width, $height) = getimagesize($file->getPathname());
                $data['imagewidth'] = $width;
                $data['imageheight'] = $height;
            }
            // 插入数据到附件表
            SystemAttachment::create($data);
            // 返回JSON格式的成功响应
            $this->json($data);
        } else {
            $this->json([], 400, '上传失败');
        }
    }
}
