<?php
declare(strict_types=1);

namespace app\common\util;

use app\logic\system\ConfigLogic;
use app\model\system\SystemAttachment;
use mowzs\lib\helper\MimeHelper;
use think\exception\ValidateException;
use think\facade\Filesystem;
use think\facade\Log;

class RemoteFileUtil
{
    /**
     * 文件类型
     * @var array|string[]
     */
    protected array $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

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

    public function __construct()
    {
        // 初始化配置
        $this->max_size = $this->convertMbToBytes(ConfigLogic::instance()->getConfigValue('file_size'));
        $this->allowed_types = str2arr(ConfigLogic::instance()->getConfigValue('file_type'));
        $this->storage_driver = ConfigLogic::instance()->getConfigValue('storage_driver');
    }

    /**
     * 下载远程文件并保存
     *
     * @param string $url 远程文件的URL
     * @param string|null $savePath 保存路径（可选）
     * @return array 成功返回文件信息，失败抛出异常
     * @throws \Exception
     */
    public function downloadAndSave(string $url, ?string $savePath = null): array
    {
        $tmpFile = null; // 用于存储临时文件路径以便最后清理
        try {
            // 使用 cURL 获取远程文件内容
            $ch = curl_init($url);
            if (!$ch) {
                throw new \Exception('cURL初始化失败');
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 跟踪重定向
            $fileContent = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch));
            }
            curl_close($ch);

            // 检查文件头以获取文件信息
            $headers = get_headers($url, true);
            $mimeType = $headers['Content-Type'] ?? '';
            $fileSize = (int)($headers['Content-Length'] ?? 0);

            // 创建临时文件并将内容写入
            $tmpFile = tempnam(sys_get_temp_dir(), 'remote_');
            if (!file_put_contents($tmpFile, $fileContent)) {
                throw new \Exception('无法写入临时文件');
            }

            // 验证文件
            $tmpFile = $this->validateFile($tmpFile, $mimeType, $fileSize);

            // 确定保存路径
            $path = $savePath ?: '/';

            // 创建 think\File 对象
            $file = new \think\File($tmpFile, false);

            // 使用 Filesystem facade 处理文件上传
            $disk = Filesystem::disk($this->storage_driver);
            $savedPath = $disk->putFile($path, $file, 'md5');

            if ($savedPath) {
                // 成功上传后 获取上传信息
                $data = [
                    'uid' => 0, // 假设用户ID存储在session中，这里可以根据实际情况调整
                    'name' => basename($tmpFile), // 使用更新后的文件名
                    'path' => $savedPath,
                    'url' => $disk->url($savedPath),
                    'mime' => mime_content_type($tmpFile), // 使用实际MIME类型
                    'ext' => pathinfo($tmpFile, PATHINFO_EXTENSION), // 使用更新后的扩展名
                    'size' => $fileSize,
                    'md5' => md5_file($tmpFile),
                    'sha1' => sha1_file($tmpFile),
                    'driver' => $this->storage_driver,
                    'create_time' => time(),
                    'update_time' => time(),
                ];

                // 如果是图片文件，获取宽度和高度
                if (in_array(mime_content_type($tmpFile), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                    list($width, $height) = getimagesize($tmpFile);
                    $data['imagewidth'] = $width;
                    $data['imageheight'] = $height;
                }

                // 插入数据到附件表
                SystemAttachment::create($data);

                // 删除临时文件
                unlink($tmpFile);

                return $data;
            } else {
                throw new \Exception("文件保存失败");
            }
        } catch (\Exception $e) {
            // 清理临时文件（如果存在）
            if ($tmpFile && file_exists($tmpFile)) {
                unlink($tmpFile);
            }
            Log::error('Remote file download failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 文件验证
     *
     * @param string $filePath 文件路径
     * @param string $providedMimeType
     * @param int $fileSize 文件大小
     * @return string
     * @throws \Exception
     */
    protected function validateFile(string $filePath, string $providedMimeType, int $fileSize): string
    {
        // 检查文件大小
        if ($fileSize > $this->max_size) {
            throw new ValidateException('文件大小超过限制');
        }

        // 获取文件的实际 MIME 类型
        $actualMimeType = mime_content_type($filePath);

        // 如果提供的 MIME 类型与实际的 MIME 类型不匹配，使用实际的 MIME 类型
        if ($providedMimeType !== $actualMimeType) {
            Log::warning("提供的MIME类型与实际的MIME类型不匹配: 提供的: {$providedMimeType}, 实际的: {$actualMimeType}");
            $mimeType = $actualMimeType;
        } else {
            $mimeType = $providedMimeType;
        }

        // 根据实际的 MIME 类型获取扩展名
        $ext = MimeHelper::instance()->getExtensionByMimeType($mimeType);

        // 确保 ext 是数组
        if (!is_array($ext)) {
            $ext = [$ext];
        }

        // 检查 $ext 中任意值是否在 $this->allowed_types 中
        $intersection = array_intersect($ext, $this->allowed_types);

        if (empty($intersection)) {
            throw new ValidateException('不允许的文件类型: ' . implode(',', $ext));
        }

        // 选择第一个交集作为最终的扩展名
        $finalExt = reset($intersection);

        // 更新文件路径以反映正确的扩展名
        $newFilePath = pathinfo($filePath, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($filePath, PATHINFO_FILENAME) . '.' . $finalExt;

        // 重命名文件
        if (!rename($filePath, $newFilePath)) {
            throw new \Exception('无法重命名文件以更新其扩展名');
        }

        // 更新 filePath 变量以便后续使用
        return $newFilePath;
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
}
