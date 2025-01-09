<?php
declare (strict_types=1);

namespace app\common\util;

use app\model\system\SystemAttachment;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;

/**
 * ueditor 配置信息
 */
class AttachmentUtil
{
    // 定义允许的文件类型
    protected static $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg'];
    protected static $fileExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', '7z'];


    /**
     * @return array
     */
    public static function getConfig(): array
    {
        return [
            // 上传图片配置项
            'imageActionName' => 'save',  // 执行上传图片的action名称
            'imageFieldName' => 'file',          // 提交的图片表单名称
            'imageMaxSize' => 8048000,           // 上传大小限制，单位B (8MB)
            'imageAllowFiles' => [               // 上传图片格式显示
                '.png', '.jpg', '.jpeg', '.gif', '.bmp'
            ],
            'imageCompressEnable' => false,      // 是否压缩图片，默认是true
            'imageCompressBorder' => 1600,       // 图片压缩最长边限制
            'imageInsertAlign' => 'none',        // 插入的图片浮动方式
            'imageUrlPrefix' => '',              // 图片访问路径前缀
            'imagePathFormat' => '/public/uploads/bd_ueditor/image/{yyyy}{mm}{dd}/{time}{rand:6}',  // 上传保存路径

            // 涂鸦图片上传配置项
            'scrawlActionName' => 'uploadscrawl',  // 执行上传涂鸦的action名称
            'scrawlFieldName' => 'file',           // 提交的图片表单名称
            'scrawlPathFormat' => '/public/uploads/bd_ueditor/image/{yyyy}{mm}{dd}/{time}{rand:6}',  // 上传保存路径
            'scrawlMaxSize' => 2048000,            // 上传大小限制，单位B (2MB)
            'scrawlUrlPrefix' => '',               // 图片访问路径前缀
            'scrawlInsertAlign' => 'none',         // 插入的图片浮动方式

            // 截图工具上传
            'snapscreenActionName' => 'save',  // 执行上传截图的action名称
            'snapscreenPathFormat' => '/public/uploads/bd_ueditor/image/{yyyy}{mm}{dd}/{time}{rand:6}',  // 上传保存路径
            'snapscreenUrlPrefix' => '',           // 图片访问路径前缀
            'snapscreenInsertAlign' => 'none',     // 插入的图片浮动方式

            // 抓取远程图片配置
            'catcherLocalDomain' => [             // 允许抓取的本地域名
                '127.0.0.1',
                'localhost',
                'img.baidu.com'
            ],
            'catcherActionName' => 'catchimage',  // 执行抓取远程图片的action名称
            'catcherFieldName' => 'source',       // 提交的图片列表表单名称
            'catcherPathFormat' => '/public/uploads/bd_ueditor/image/{yyyy}{mm}{dd}/{time}{rand:6}',  // 上传保存路径
            'catcherUrlPrefix' => '',             // 图片访问路径前缀
            'catcherMaxSize' => 2048000,          // 上传大小限制，单位B (2MB)
            'catcherAllowFiles' => [              // 抓取图片格式显示
                '.png', '.jpg', '.jpeg', '.gif', '.bmp'
            ],

            // 上传视频配置
            'videoActionName' => 'save',   // 执行上传视频的action名称
            'videoFieldName' => 'file',           // 提交的视频表单名称
            'videoPathFormat' => '/public/uploads/bd_ueditor/video/{yyyy}{mm}{dd}/{time}{rand:6}',  // 上传保存路径
            'videoUrlPrefix' => '',               // 视频访问路径前缀
            'videoMaxSize' => 102400000,          // 上传大小限制，单位B (100MB)
            'videoAllowFiles' => [                // 上传视频格式显示
                '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid'
            ],

            // 上传文件配置
            'fileActionName' => 'save',     // 执行上传文件的action名称
            'fileFieldName' => 'file',            // 提交的文件表单名称
            'filePathFormat' => '/public/uploads/bd_ueditor/file/{yyyy}{mm}{dd}/{time}{rand:6}',  // 上传保存路径
            'fileUrlPrefix' => '',                // 文件访问路径前缀
            'fileMaxSize' => 51200000,            // 上传大小限制，单位B (50MB)
            'fileAllowFiles' => [                 // 上传文件格式显示
                '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid', '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml'
            ],

            // 列出指定目录下的图片
            'imageManagerActionName' => 'listimage',  // 执行图片管理的action名称
            'imageManagerListPath' => '/public/uploads/bd_ueditor/image/',  // 指定要列出图片的目录
            'imageManagerListSize' => 20,             // 每次列出文件数量
            'imageManagerUrlPrefix' => '',            // 图片访问路径前缀
            'imageManagerInsertAlign' => 'none',      // 插入的图片浮动方式
            'imageManagerAllowFiles' => [             // 列出的文件类型
                '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.svg'
            ],

            // 列出指定目录下的文件
            'fileManagerActionName' => 'listfile',    // 执行文件管理的action名称
            'fileManagerListPath' => '/public/uploads/bd_ueditor/file/',  // 指定要列出文件的目录
            'fileManagerUrlPrefix' => '',             // 文件访问路径前缀
            'fileManagerListSize' => 20,              // 每次列出文件数量
            'fileManagerAllowFiles' => [              // 列出的文件类型
                '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid', '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml'
            ]
        ];
    }

    /**
     * 获取图片列表
     *
     * @param int $userId 用户ID
     * @param int $start 起始偏移量
     * @param int $size 每页显示的数量
     * @return array
     */
    public static function getListImage(mixed $userId, int $start = 0, int $size = 20): array
    {
        return self::getAttachments($userId, self::$imageExtensions, $start, $size, 'listimage');
    }

    /**
     * 获取文件列表
     *
     * @param int $userId 用户ID
     * @param int $start 起始偏移量
     * @param int $size 每页显示的数量
     * @return array
     */
    public static function getListFile(mixed $userId, int $start = 0, int $size = 20): array
    {
        return self::getAttachments($userId, self::$fileExtensions, $start, $size, 'listfile');
    }

    /**
     * 获取附件列表（公共逻辑）
     *
     * @param int $userId 用户ID
     * @param array $extensions 允许的文件扩展名
     * @param int $start 起始偏移量
     * @param int $size 每页显示的数量
     * @param string $cacheKeyPrefix 缓存键前缀
     * @param string $returl
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private static function getAttachments(mixed $userId, array $extensions, int $start = 0, int $size = 20, string $cacheKeyPrefix = '', string $returl = 'ueditor'): array
    {
        // 生成缓存键，包含 userId、start 和 size 参数
        $cacheKey = "{$cacheKeyPrefix}_{$userId}_{$start}_{$size}";

        // 从缓存中获取附件列表
        if (Cache::has($cacheKey)) {
            $paginatedData = Cache::get($cacheKey);
        } else {
            // 使用 limit 进行分页查询，只选择需要的字段
            $query = SystemAttachment::where('uid', $userId)
                ->whereIn('ext', $extensions)
                ->field(['url', 'name', 'size', 'mime', 'create_time'])  // 指定查询字段
                ->order('create_time', 'desc')  // 按上传时间降序排列
                ->limit($start, $size)  // 使用 limit 实现分页，第一个参数是偏移量，第二个参数是每页大小
                ->select();

            // 获取总条数
            $total = SystemAttachment::where('uid', $userId)
                ->whereIn('ext', $extensions)
                ->count();

            // 将分页结果转换为数组并缓存
            $paginatedData = [
                'list' => $query->toArray(),
                'total' => $total,
                'start' => $start,
            ];

            // 缓存分页数据
            Cache::tag('ueditor')->set($cacheKey, $paginatedData, 3600);
        }

        // 判断是否已经加载完所有内容
        $noMoreData = ($start + $size) >= $paginatedData['total'];

        return [
            'state' => 'SUCCESS',
            'list' => array_map(function ($item) {
                return [
                    'url' => $item['url'],  // 文件链接
                    'name' => $item['name'],  // 文件名
                    'size' => $item['size'],  // 文件大小
                    'type' => $item['mime'],  // 文件类型
                    'mtime' => date('Y-m-d H:i:s', strtotime($item['create_time']))  // 上传时间
                ];
            }, $paginatedData['list']),
            'start' => $paginatedData['start'],
            'total' => $paginatedData['total'],
            'noMoreData' => $noMoreData  // 是否已经加载完所有内容
        ];
    }
}
