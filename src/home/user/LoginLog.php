<?php
declare(strict_types=1);

namespace app\home\user;

use app\common\controllers\BaseUser;
use app\model\user\UserLoginLog;
use think\db\exception\DbException;
use think\template\exception\TemplateNotFoundException;

class LoginLog extends BaseUser
{
    /**
     * @var array
     */
    protected array $tables;
    /**
     * 是否开启分页
     * @var bool
     */
    protected bool $is_page = true;
    /**
     * @var array|string[]
     */
    protected array $search;

    protected function initialize(): void
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->setParams();
    }

    /**
     * @return string
     * @throws DbException
     */
    public function index(): string
    {
        if ($this->isLayTable()) {
            $data = UserLoginLog::where('uid', $this->user['id'])->order('id desc')->paginate(20)->toArray();
            $this->success($data);
        }
        //渲染页面
        try {
            return $this->fetch();
        } catch (TemplateNotFoundException $exception) {
            //模板不存在时 尝试读取公用模板
            return $this->fetch('common@page_table');
        }
    }

    /**
     * @return void
     */
    protected function setParams(): void
    {
        // 定义表格字段
        $this->tables['fields'] = [
            [
                'field' => 'ip',
                'title' => '登录ip',
            ],
            [
                'field' => 'create_time',
                'title' => '登录时间',
            ],
        ];

//        // 定义表单字段
//        $this->forms['fields'] = [
//            [
//                'type' => 'text',
//                'name' => 'uid',
//                'label' => '用户ID',
//                'readonly' => true,
//            ],
//            [
//                'type' => 'number',
//                'name' => 'points',
//                'label' => '积分变动',
//                'readonly' => true,
//            ],
//            [
//                'type' => 'text',
//                'name' => 'type',
//                'label' => '变动类型',
//                'readonly' => true,
//            ],
//            [
//                'type' => 'textarea',
//                'name' => 'desc',
//                'label' => '变动描述',
//                'readonly' => true,
//            ],
//            [
//                'type' => 'datetime',
//                'name' => 'create_time',
//                'label' => '创建时间',
//                'readonly' => true,
//            ],
//        ];

        // 定义搜索条件
        $this->search = [
            'ip#=#ip',
            'create_time#between#create_time',
        ];
    }
}
