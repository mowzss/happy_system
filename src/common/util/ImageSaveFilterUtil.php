<?php
declare (strict_types=1);

namespace app\common\util;

use think\facade\Log;
use app\logic\system\ConfigLogic;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 表单 image 类型字段的远程图片本地化
 *
 * 与正文富文本的远程图片下载（ContentSaveFilterUtil）使用同一个模块开关
 * is_content_image_down，区别是本类处理的是 image 字段保存的单个图片地址，
 * 而富文本处理的是 content 中的 img 标签。
 */
class ImageSaveFilterUtil extends UtilBase
{
    /**
     * 当前模块配置
     * @var mixed
     */
    protected mixed $config;
    /**
     * 网站配置
     * @var mixed
     */
    protected mixed $web_config;

    /**
     * 初始化
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->web_config = ConfigLogic::instance()->getConfigValue();
        $this->config = $this->web_config[$this->app->request->layer(true)] ?? [];
    }

    /**
     * 下载 image 类型字段中的远程图片，并用本地地址替换
     *
     * 下载失败、字段非远程地址或属于本站存储域名时保留原值。
     *
     * @param array $data 提交数据
     * @param array $fields 表单字段定义（forms['fields']）
     * @return array
     */
    public function downloadImageFields(array $data, array $fields): array
    {
        if (!$this->isDownImage()) {
            return $data;
        }
        foreach ($fields as $field) {
            if (!is_array($field) || ($field['type'] ?? '') !== 'image') {
                continue;
            }
            $name = (string)($field['name'] ?? '');
            if ($name === '' || !isset($data[$name]) || !is_string($data[$name])) {
                continue;
            }
            $oldSrc = trim($data[$name]);
            if (!$this->isRemoteUrl($oldSrc) || $this->isDownFilterHost($oldSrc)) {
                continue;
            }
            try {
                $file_info = (new RemoteFileUtil)->downloadAndSave($oldSrc);
                $data[$name] = $file_info['url'];
            } catch (\Exception $e) {
                Log::error('保存远程图片失败:' . $e->getMessage());
            }
        }
        return $data;
    }

    /**
     * 校验是否开启远程图片下载
     * @return bool
     */
    protected function isDownImage(): bool
    {
        return !empty($this->config['is_content_image_down']);
    }

    /**
     * 是否为需要下载的远程地址（http(s):// 或协议相对 //）
     * @param string $url
     * @return bool
     */
    protected function isRemoteUrl(string $url): bool
    {
        return (bool)preg_match('#^(?:https?:)?//#i', $url);
    }

    /**
     * 检查是否为当前站点或当前存储域名，这类地址无需重复下载
     * @param string $url
     * @return bool
     */
    protected function isDownFilterHost(string $url = ''): bool
    {
        $host = match ($this->web_config['system']['storage_driver'] ?? 'local') {
            'oss' => $this->web_config['system']['oss_domain'] ?? '',
            'qiniu' => $this->web_config['system']['qiniu_domain'] ?? '',
            default => $this->app->request->host(true),
        };
        if ($host === '') {
            return false;
        }
        return stripos($url, (string)$host) !== false;
    }
}
