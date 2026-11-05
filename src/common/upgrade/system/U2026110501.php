<?php


namespace app\common\upgrade\system;

use app\model\system\SystemConfig;
use app\model\system\SystemConfigGroup;
use think\Exception;

class U2026110501
{
    /**
     * 新增百度推送移动端（wap）渠道配置
     *
     * 百度 site/token 按站点绑定，PC 端 token 无法推送移动端域名链接。
     * 移动端域名需在百度站长平台单独验证并获取独立 token。
     *
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        $group_id = SystemConfigGroup::where([
            'module' => 'system',
            'title' => 'Api设置',
        ])->value('id');

        if (empty($group_id)) {
            throw new Exception('分组id为空：Api设置');
        }

        $configs = [
            [
                'name' => 'baidu_push_wap_domain',
                'type' => 'text',
                'title' => '百度 PUSH 移动端域名',
                'group_id' => $group_id,
                'options' => '',
                'help' => '移动端推送域名，需包含http://或https:// 无需/结尾；必须与百度站长平台中已验证的移动端站点一致',
                'value' => '',
                'extend' => null,
                'list' => '0',
                'module' => 'system',
                'status' => '1',
            ]
        ];

        foreach ($configs as $config) {
            $exists = SystemConfig::where([
                'name' => $config['name'],
                'module' => 'system',
            ])->find();

            if (!empty($exists)) {
                continue;
            }

            SystemConfig::create($config);
        }
    }
}
