<?php

namespace app\common\util;

use app\model\system\SystemConfig;
use HTMLPurifier;
use HTMLPurifier_Config;
use mowzs\lib\baidu\AipNlp;
use mowzs\lib\module\service\TagBaseService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;

class ContentSaveFilterUtil extends UtilBase
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
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->web_config = SystemConfig::getConfigValue();
        $this->config = $this->web_config[$this->app->request->layer(true)];
    }

    /**
     * 校验是否开启下载图片
     * @return bool
     */
    protected function isDownImage(): bool
    {
        if (!empty($this->config['is_content_image_down'])) {
            return true;
        }
        return false;
    }

    /**
     * 检测是否开启获取缩略图
     * @return bool
     */
    protected function isImagesThum(): bool
    {
        if (!empty($this->config['is_content_thum'])) {
            return true;
        }
        return false;
    }

    /**
     * 检测是否开启内容过滤
     * @return bool
     */
    protected function isPuriferHtml(): bool
    {
        if (!empty($this->config['is_content_purifer_html'])) {
            return true;
        }
        return false;
    }

    /**
     * 检查排除域名
     * @param string $url
     * @return bool
     */
    protected function isDownFilterHost(string $url = ''): bool
    {
        $host = match ($this->web_config['system']['storage_driver']) {
            'oss' => $this->web_config['system']['oss_domain'],
            'qiniu' => $this->web_config['system']['qiniu_domain'],
            default => $this->app->request->host(true),
        };
        if (stristr($url, $host) !== false) {
            return true;
        }
        return false;
    }

    /**
     * 钩子总检测
     * @return bool
     */
    protected function cheek(): bool
    {
        if ($this->isDownImage()) {
            return true;
        }
        if ($this->isImagesThum()) {
            return true;
        }
        if ($this->isPuriferHtml()) {
            return true;
        }
//        if ($this->cheek_search()) {
//            return true;
//        }
//        if ($this->cheek_push_baidu()) {
//            return true;
//        }
//        if ($this->cheek_push_bing()) {
//            return true;
//        }
        return false;
    }

    /**
     * 内容过滤
     * @param string $content
     * @return string
     */
    public function puriferContent($content): string
    {
        $config = HTMLPurifier_Config::createDefault();
        //设置允许出现的html标签
        $config->set('HTML.Allowed', $this->config['content_purifer_html'] ?? 'h2,h3,h4,h5,p,strong,a[href|title],span[style],img[width|height|alt|src],table');
        // 设置允许出现的CSS样式属性
        $config->set(
            'CSS.AllowedProperties',
            $this->config['content_purifer_css'] ?? 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align'
        );
        $config->set('AutoFormat.RemoveEmpty', (bool)$this->config['content_purifer_remove_empty']);
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($content);
    }

    /**
     * 处理数据
     * @param $info
     * @return array
     * @throws DbException
     */
    public function setProcessingData($info): array
    {
        $info = $this->setTag($info);
        //检测功能是否开启
        if ($this->cheek()) {
            if (!empty($info['content'])) {
                //过滤内容
                if ($this->isPuriferHtml()) {
                    $updata['content'] = $info['content'] = $this->puriferContent($info['content']);
                }
                $pattern = "/<img[^>]*src=[\'\"]((?:https?:)?\/\/[^\s\'\"]+\.(?:gif|jpg|png|jpeg|webp))[\'\"][^>]*>/i";
                preg_match_all($pattern, $info['content'], $images);
                $newContent = $info['content'];
                //下载远程图片
                if ($this->isDownImage()) {
                    foreach ($images[1] as $image) {
                        $oldSrc = $image;
                        if (!$this->isDownFilterHost($oldSrc)) {
                            try {
                                $file_info = (new RemoteFileUtil)->downloadAndSave($oldSrc);
                                $newSrc = $file_info['url'];
                            } catch (\Exception $e) {
                                Log::error('保存远程图片失败:' . $e->getMessage());
                                $newSrc = $oldSrc;
                            }
                            $newContent = str_replace($oldSrc, $newSrc, $newContent);
                        }
                    }
                    $updata['content'] = $info['content'] = $newContent;
                }
                //处理缩略图
                if ($this->isImagesThum()) {
                    preg_match_all($pattern, $info['content'], $images);
                    $picurls = $images[1];
                    if (count($picurls) > $this->config['is_content_thum_num']) {
                        $picurls = array_slice($picurls, 0, $this->config['is_content_thum_num']);
                    }
                    if (!empty($picurls)) {
                        $picurl = implode(',', $picurls);
                        $ispic = 1;
                    }
                    $updata['images'] = $picurl ?? '';
                    $updata['is_pic'] = $ispic ?? 0;
                }
            }
            return array_merge($info, $updata);
        }
        return $info;
    }

    /**
     * @param $data
     * @return mixed
     * @throws \think\Exception
     */
    protected function setTag($data): mixed
    {
        if (empty($data['huati']) && empty($data['tag'])) {
            $data['huati'] = (new AipNlp())->getStringTag(
                $data['title'],
                get_word(del_html($data['content']), 300)
            );
        }
        //处理tag
        $tags = [];
        if (!empty($data['huati']) && empty($data['tag'])) {
            $huati = str2arr($data['huati']);
            foreach ($huati as $k => $v) {
                $tags[$k] = TagBaseService::instance()->getTagIdByTitle($v);
            }
            $data['tag'] = arr2str($tags);
        }
        return $data;
    }
}
