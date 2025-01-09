<?php
declare (strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemAttachment;
use League\Flysystem\FilesystemException;
use mowzs\lib\Forms;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Filesystem;

class Attachment extends BaseAdmin
{
    use CrudTrait;

    protected array $type;
    /**
     * @var array|string[]
     */
    protected array $types;

    public function __construct(App $app, SystemAttachment $attachment)
    {
        parent::__construct($app);
        $this->model = $attachment;
        $this->type = [
            'all' => '',
            'image' => 'jpg,jpeg,png,gif,bmp,webp,svg',
            'audio' => 'mp3,wav,ogg,mpga',
            'video' => 'mp4,mov,flv,avi',
            'document' => 'doc,docx,xls,xlsx,ppt,pptx',
        ];
        $this->types = [
            'all' => '全部',
            'image' => '图片',
            'audio' => '音频',
            'video' => '视频',
            'document' => '文档',
        ];
        $this->tables = [
            'fields' => [
                [
                    'field' => 'id',
                    'title' => 'ID',
                    'width' => 80,
                    'sort' => true,
                ],
                [
                    'field' => 'name',
                    'title' => '文件名',
                    'align' => 'content'
                ], [
                    'field' => 'url',
                    'title' => '链接地址',
                    'align' => 'content'
                ], [
                    'field' => 'mime',
                    'title' => '文件类型',
                    'align' => 'content'
                ], [
                    'field' => 'ext',
                    'title' => '文件后缀',
                    'align' => 'content'
                ], [
                    'field' => 'size',
                    'title' => '文件大小',
                    'align' => 'content'
                ], [
                    'field' => 'driver',
                    'title' => '存储驱动',
                    'align' => 'content'
                ],
            ],
            'top_button' => [
                [
                    'event' => 'add',
                    'name' => '上传文件',
                ],
                [
                    'event' => 'cleanDuplicates',
                    'name' => '清理重复记录',
                    'class' => 'layui-btn-danger',//默认包含 layui-btn layui-btn-xs
                    'extra' => [
                        'data-load' => urls('cleanDuplicates')
                    ]
                ],
            ],
            'right_button' => [
                ['event' => 'del']
            ]
        ];
    }

    /**
     * 清理重复记录
     * @auth true
     * @return void
     */
    public function cleanDuplicates(): void
    {
        $this->model->removeDuplicateUidMd5Records();
        $this->success('清理成功');
    }

    /**
     * 上传文件
     * @return string
     * @throws Exception
     */
    public function add(): string
    {
        $this->forms = [
            'fields' => [
                [
                    'type' => 'files',
                    'name' => 'title',
                    'label' => '上传文件',
                    'required' => true
                ]
            ]
        ];
        if ($this->request->isPost()) {
            $this->success('添加成功');
        }
        if (empty($this->forms['fields'])) {
            $this->error('未设置 forms 参数');
        }
        return Forms::instance()->render($this->forms['fields']);
    }

    /**
     * @param string $ids
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function delete(string $ids = ''): void
    {
        if ($this->request->isPost()) {
            $ids = $this->request->param('ids');
            if (is_null($ids)) {
                $this->error('id不能为空');
            }
            if (is_array($ids)) {
                $this->error('文件不支持批量删除');
            } else {
                // 单个删除
                $record = $this->model->find($ids);
                if ($record) {
                    try {
                        Filesystem::disk($record->driver)->delete($record->path);
                        $record->delete();
                        $this->success('删除成功');
                    } catch (FilesystemException $e) {
                        $this->error('删除失败', $e->getMessage());
                    }
                } else {
                    $this->error('记录不存在');
                }
            }
        } else {
            $this->error('请求错误');
        }
    }
}
