<?php
declare (strict_types=1);

namespace app\common\util;

class CrudUtil
{

    public static function getButtonHtml(mixed $buttons = [], string $type = 'right'): string
    {
        // 如果 buttons 为 false，直接返回空字符串
        if ($buttons === false) {
            return '';
        }

        // 如果 buttons 为空数组，默认添加编辑和删除按钮
        if (empty($buttons)) {

            if ($type === 'right') {
                $buttons = [
                    ['event' => 'edit', 'name' => '编辑'],
                    ['event' => 'del', 'name' => '删除']
                ];
            } elseif ($type === 'top') {
                $buttons = [
                    ['event' => 'add', 'name' => '添加'],
                    ['event' => 'del', 'name' => '删除']
                ];
            }

        }

        $html = '';
        foreach ($buttons as $button) {
            // 检查是否应该跳过该按钮
            if (isset($button['edit']) && $button['edit'] === false) {
                continue;
            }
            if (isset($button['del']) && $button['del'] === false) {
                continue;
            }

            // 设置默认名称
            $name = isset($button['name']) && !empty($button['name']) ? $button['name'] : ($button['event'] === 'edit' ? '编辑' : ($button['event'] === 'del' ? '删除' : ($button['event'] === 'add' ? '添加' : '')));

            // 构建URL并替换占位符为 {{d.xx}}
            $url = isset($button['url']) ? preg_replace_callback('/__([\w]+)__/i', function ($ar) {
                return "{{d." . $ar[1] . "}}";
            }, $button['url']) : '';

            // 设置默认类名，根据 type 判断是否添加 layui-btn-xs
            $className = 'layui-btn ' . ($type !== 'top' ? 'layui-btn-xs ' : 'layui-btn-sm ') . (!empty($button['class']) ? $button['class'] : '');
            if ($button['event'] === 'del') {
                $className .= ' layui-btn-danger'; // 为删除按钮添加危险样式
            }

            // 设置事件属性
            $eventAttr = !empty($button['event']) ? "lay-event=\"{$button['event']}\"" : '';

            // 根据 type 添加特定属性
            $typeAttr = '';
            if (!empty($button['type'])) {
                $typeAttr = "{$button['type']}=\"{$url}\"";
            }

            // 处理额外的HTML属性
            $extraAttrs = '';
            if (isset($button['extra']) && is_array($button['extra'])) {
                foreach ($button['extra'] as $key => $value) {
                    $extraAttrs .= " {$key}=\"{$value}\"";
                }
            }

            // 生成按钮HTML
            if ($name !== '') {
                $html .= "<button class=\"{$className}\" {$eventAttr} {$typeAttr}{$extraAttrs}>{$name}</button>";
            }
        }

        return $html;
    }

    /**
     * 获取必填字段
     * @param array $fields
     * @return array
     */
    public static function getRequiredFieldNames(array $fields): array
    {
        // 过滤出 required 为 true 的字段
        $requiredFields = array_filter($fields, function ($field) {
            return isset($field['required']) && $field['required'] === true;
        });

        // 创建 name => label 的关联数组
        $requiredFieldPairs = [];
        foreach ($requiredFields as $field) {
            if (isset($field['name']) && isset($field['label'])) {
                $requiredFieldPairs[$field['name']] = $field['label'];
            }
        }

        return $requiredFieldPairs;
    }
}
