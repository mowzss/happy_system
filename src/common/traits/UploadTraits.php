<?php
declare (strict_types=1);

namespace app\common\traits;

use app\common\util\AttachmentUtil;
use app\model\system\SystemAttachment;
use app\model\system\SystemConfig;
use mowzs\lib\helper\MimeHelper;
use mowzs\lib\helper\UserHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Filesystem;
use think\facade\View;
use think\File;
use think\response\Json;

trait UploadTraits
{

    /**
     * 文件类型
     * @var array|string[]
     */
    protected array $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    /**
     * 文件大小
     * @var int|float
     */
    protected int|float $max_size = 2 * 1024 * 1024; // 2MB
    /**
     * 存储驱动
     * @var mixed
     */
    protected mixed $storage_driver;

    protected function initialize(): void
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->max_size = $this->convertMbToBytes(SystemConfig::getConfigValue('file_size'));
        $this->allowed_types = str2arr(SystemConfig::getConfigValue('file_type'));
        $this->storage_driver = SystemConfig::getConfigValue('storage_driver');
    }

    /**
     * 上传接口
     * @login true
     * @return \think\response\View
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index(): \think\response\View
    {
        $data = [
            'exts' => str2arr(sys_config('file_type')),
            'mimes' => MimeHelper::instance()->getMimeTypesByExtensions($this->allowed_types),
        ];
        View::config(['view_path' => __DIR__ . '/view/', 'view_suffix' => 'js']);
        return \view('/uploads', $data)->contentType('application/x-javascript');
    }

    /**
     * 保存文件
     * @login true
     * @return \think\response\Json
     */
    public function save(): \think\response\Json
    {
        // 获取表单上传文件
        $file = $this->app->request->file('file');

        if (empty($file)) {
            return json(['code' => 1, 'msg' => '未选择文件']);
        }

        try {
            // 验证文件
            $this->validateFile($file);

            // 使用Filesystem facade 处理文件上传
            $disk = Filesystem::disk($this->storage_driver); // 可以根据配置切换到其他存储驱动
            $filename = $file->getOriginalName();
            $path = $disk->putFile('/', $file, 'md5');

            if ($path) {
                // 成功上传后 获取上传信息
                $data = [
                    'uid' => UserHelper::instance()->getUserId() ?: 0, // 假设用户ID存储在session中
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
                return json(['code' => 0, 'msg' => '上传成功', 'data' => $data]);
            } else {
                // 上传失败返回错误信息
                throw new \Exception("文件上传失败");
            }
        } catch (ValidateException $e) {
            // 验证失败返回错误信息
            return json(['code' => 1, 'msg' => $e->getError()]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * UEditor 相关操作
     *
     * @param string $action 操作类型 (config, listimage, listfile)
     * @return Json
     */
    public function ueditor(string $action = ''): Json
    {
        // 获取当前用户ID
        $userId = UserHelper::instance()->getUserId();
        // 获取 start 和 size 参数
        $start = $this->request->get('start/d', 0);  // 确保 start 是整数，默认值为 0
        $size = $this->request->get('size/d', 20);   // 确保 size 是整数，默认值为 20

        // 验证 size 参数，防止过大的分页请求
        if ($size <= 0 || $size > 100) {
            return json([
                'state' => 'ERROR',
                'msg' => 'Invalid page size'
            ]);
        }

        switch ($action) {
            case 'config':
                // 返回 UEditor 配置
                return json(AttachmentUtil::getConfig());

            case 'listimage':
                // 获取图片列表
                return json(AttachmentUtil::getListImage($userId, $start, $size));

            case 'listfile':
                // 获取文件列表
                return json(AttachmentUtil::getListFile($userId, $start, $size));

            default:
                return json(['state' => 'ERROR', 'msg' => '无效的操作类型']);
        }
    }

    /**
     * 文件验证
     *
     * @param File $file
     * @throws \Exception
     */
    protected function validateFile(File $file): void
    {
        // 检查文件大小
        if ($file->getSize() > $this->max_size) {
            throw new ValidateException('文件大小超过限制');
        }

        // 检查文件类型

        $mime = $file->getMime();
        $ext = MimeHelper::instance()->getExtensionByMimeType($mime);
        if (!in_array($ext, $this->allowed_types)) {
            throw new ValidateException('不允许的文件类型');
        }
    }

    /**
     * 将以 MB 为单位的文件大小限制转换为字节数
     *
     * @param float|string $sizeInMb 文件大小限制（以MB为单位）
     * @return int 字节数
     */
    protected function convertMbToBytes(float|string $sizeInMb): int
    {
        if (is_numeric($sizeInMb)) {
            return (int)($sizeInMb * 1024 * 1024);
        } elseif (is_string($sizeInMb) && preg_match('/^(\d+(\.\d+)?)\s*(MB|mb|M|b)?$/', $sizeInMb, $matches)) {
            // 提取数字部分并转换为字节
            $numericValue = (float)$matches[1];
            return (int)($numericValue * 1024 * 1024);
        } else {
            throw new \InvalidArgumentException('无效的文件大小格式');
        }
    }

    /**
     * 远程下载文件并保存
     *
     * @param string $url 远程文件的URL
     * @param string|null $savePath 保存路径（可选）
     * @return \think\response\Json
     */
    public function downloadAndSave(string $url, ?string $savePath = null): \think\response\Json
    {
        try {
            // 初始化 cURL 会话
            $ch = curl_init($url);
            if (!$ch) {
                throw new \Exception('cURL初始化失败');
            }

            // 设置选项以获取文件内容
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 跟踪重定向

            // 执行请求并检查是否有错误
            $fileContent = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch));
            }

            // 获取文件信息
            $fileInfo = get_headers($url, 1);
            $mimeType = $fileInfo['Content-Type'] ?? '';
            $fileSize = (int)($fileInfo['Content-Length'] ?? 0);

            // 关闭 cURL 会话
            curl_close($ch);

            // 创建临时文件
            $tmpFile = tempnam(sys_get_temp_dir(), 'remote_');
            if (!file_put_contents($tmpFile, $fileContent)) {
                throw new \Exception('无法写入临时文件');
            }

            // 创建 File 对象以便后续操作
            $file = new File($tmpFile, false);
            $file->setOriginalMime($mimeType);
            $file->setSize($fileSize);

            // 验证文件
            $this->validateFile($file);

            // 确定保存路径
            $path = $savePath ?: '/';

            // 使用 Filesystem facade 处理文件上传
            $disk = Filesystem::disk($this->storage_driver);
            $savedPath = $disk->put($path, $file, 'md5');

            if ($savedPath) {
                // 成功上传后 获取上传信息
                $data = [
                    'uid' => UserHelper::instance()->getUserId() ?: 0,
                    'name' => basename(parse_url($url, PHP_URL_PATH)),
                    'path' => $savedPath,
                    'url' => $disk->url($savedPath),
                    'mime' => $mimeType,
                    'ext' => pathinfo($file->getOriginalName(), PATHINFO_EXTENSION),
                    'size' => $fileSize,
                    'md5' => md5_file($tmpFile),
                    'sha1' => sha1_file($tmpFile),
                    'driver' => $this->storage_driver,
                    'create_time' => time(),
                    'update_time' => time(),
                ];

                // 如果是图片文件，获取宽度和高度
                if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
                    list($width, $height) = getimagesize($tmpFile);
                    $data['imagewidth'] = $width;
                    $data['imageheight'] = $height;
                }

                // 插入数据到附件表
                SystemAttachment::create($data);

                // 删除临时文件
                unlink($tmpFile);

                // 返回JSON格式的成功响应
                return json(['code' => 0, 'msg' => '下载并上传成功', 'data' => $data]);
            } else {
                // 上传失败返回错误信息
                throw new \Exception("文件保存失败");
            }
        } catch (\Exception $e) {
            // 清理临时文件（如果存在）
            if (isset($tmpFile) && file_exists($tmpFile)) {
                unlink($tmpFile);
            }
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}