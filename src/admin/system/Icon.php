<?php
declare (strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\CrudTrait;
use app\model\system\SystemIcon;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * icon图标管理
 */
class Icon extends BaseAdmin
{
    use CrudTrait;


    protected string $title = '图标管理';
    /**
     * @var
     */
    protected $list;
    /**
     * @var array|mixed
     */
    protected mixed $field;

    public function __construct(App $app, SystemIcon $systemIcon)
    {
        parent::__construct($app);
        $this->model = $systemIcon;
        $this->setParams();
    }

    /**
     * 获取全部ICON图标
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getIcons(): string
    {
        $this->list = $this->model->where('status', 1)->select();
        $this->list = $this->list->each(function ($item) {
            // 定义 CSS 文件路径和前缀
            $cssFile = $this->app->getRootPath() . $item->path;
            $prefix = $item->prefix;
            // 从缓存中获取数据
            $cacheKey = 'sys_icon_classes_' . $item->name;
            $iconClasses = $this->app->cache->get($cacheKey, []);
            if (empty($iconClasses)) {
                // 如果缓存不存在，读取并解析 CSS 文件
                $cssContent = file_get_contents($cssFile);
                // 使用正则表达式匹配以指定前缀开头的类名
                preg_match_all('/\.(' . preg_quote($prefix, '/') . '[\w-]+)/', $cssContent, $matches);
                if (count($iconClasses = array_unique($matches[1])) > 0) {
                    // 将结果存储到缓存，设置缓存时间为 24 小时
                    $this->app->cache->set($cacheKey, $iconClasses, 60);
                }
            }
            $item->css_class = $iconClasses;
        });
        $this->field = $this->request->param('field', 'icon');
        return $this->fetch();
    }

    protected function setParams(): void
    {
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
                    'title' => 'icon 名称',
                    'edit' => 'text',
                    'align' => 'content'
                ], [
                    'field' => 'prefix',
                    'title' => 'icon 前缀',
                    'edit' => 'text',
                    'align' => 'content'
                ], [
                    'field' => 'path',
                    'title' => '路径',
                    'edit' => 'text',
                    'align' => 'content'
                ], [
                    'field' => 'url',
                    'title' => '访问路径',
                    'edit' => 'text',
                ], [
                    'field' => 'list',
                    'title' => '排序',
                    'edit' => 'text',
                    'sort' => true,
                ], [
                    'field' => 'is_show',
                    'title' => '是否引用',
                    'templet' => 'switch'
                ], [
                    'field' => 'status',
                    'title' => '状态',
                    'templet' => 'switch'
                ], [
                    'field' => 'create_time',
                    'title' => '创建时间',
                ],
            ],
            'top_button' => [

            ],
            'right_button' => [

            ]

        ];
        $this->forms = [
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'title',
                    'label' => '名称',
                    'required' => true
                ], [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'icon类名',
                    'required' => true
                ], [
                    'type' => 'text',
                    'name' => 'prefix',
                    'label' => 'icon前缀',
                    'required' => true
                ], [
                    'type' => 'text',
                    'name' => 'path',
                    'label' => '路径',
                    'required' => true,
                    'help' => '路径需包含且从public开始填写'
                ], [
                    'type' => 'text',
                    'name' => 'url',
                    'label' => '前端地址',
                    'required' => true
                ], [
                    'type' => 'radio',
                    'name' => 'is_show',
                    'label' => '是否引用',
                    'options' => [
                        '1' => '显示', '0' => '隐藏'
                    ],
                    'help' => '如layui本就在后台页面中使用，则无需选择显示，如在其他应用端使用为便于后台显示图标效果，则建议选择显示',
                    'required' => true
                ]
            ]
        ];
    }
}
