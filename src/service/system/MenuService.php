<?php

namespace app\service\system;

use app\model\system\SystemMenu;
use think\Exception;

class MenuService
{
    /**
     * 插入菜单数据。
     *
     * @param array $menus 菜单数据数组。
     * @param string|null $parentSlot 父级 slot 标记。
     * @return void
     * @throws Exception
     */
    public function insertMenus(array $menus, ?string $parentSlot = null): void
    {
        foreach ($menus as $menu) {
            // 处理顶级菜单
            $parentId = $this->insertMenu($menu, null);

            if (!empty($menu['sub'])) {
                // 如果有子菜单，则递归插入子菜单，并设置父级ID
                $this->insertMenus($menu['sub'], $parentId);
            }
        }
    }

    /**
     * 插入单个菜单项。
     *
     * @param array $menu 单个菜单项的数据。
     * @param int|null $parentId 父级菜单ID。
     * @return int 返回插入菜单的ID。
     * @throws Exception
     */
    private function insertMenu(array $menu, ?int $parentId): int
    {
        $data = [
            'pid' => $parentId ?? 0,
            'title' => $menu['title'],
            'icon' => $menu['icon'] ?? '',
            'slot' => $menu['slot'] ?? '',
            'node' => $menu['node'],
            'params' => $menu['params'] ?? '',
            'class' => $menu['class'] ?? 1,
            'list' => $menu['list'] ?? 0,
            'status' => $menu['status'] ?? 1,
        ];

        try {
            $model = SystemMenu::create($data);
            return $model->id;
        } catch (\Exception $e) {
            throw new Exception("Failed to insert menu: " . $e->getMessage());
        }
    }
}
