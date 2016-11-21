<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/11/9
 * Time: 16:56
 */

namespace Lib\Model\Setting;


use Lib\Model\BaseModel;

class MenuModel extends BaseModel
{
    /**
     * 获得菜单列表
     * @return array
     */
    public function getMenus()
    {
        $menus = [];

        // 第一级目录
        $list = $this->db->select('mcc_menu', '*', [
            'AND'   => [
                'isTop' => 1,
                'type'  => 'menu-0'
            ]
        ]);
        foreach ($list as $topMenu) {
            $tmp = $this->analysisMenuInfo($topMenu);
            $children = empty($topMenu['children']) ? [] : explode(',', $topMenu['children']);
            foreach ($children as $child) {
                $menuInfo = $this->getMenuChildren($child);
                $tmp['children'][] = $menuInfo;
            }
            $menus[] = $tmp;
        }
        return $menus;
    }


    private function getMenuChildren($id)
    {
        $info = $this->db->get('mcc_menu', '*', [
            'AND' => [
                'menu_id' => $id
            ]
        ]);
        $tmp = $this->analysisMenuInfo($info);
        $children = empty($info['children']) ? [] : explode(',', $info['children']);
        foreach ($children as $child) {
            $childrenMenu = $this->getMenuChildren($child);
            $tmp['children'][] = $childrenMenu;
        }
        $menu = $tmp;
        return $menu;
    }

    /**
     * @param $info
     * @return array
     */
    private function analysisMenuInfo($info)
    {
        switch ($info['type']) {
            case 'menu-0':
            case 'menu-1':
            case 'menu-2':
                $menu = [
                    'for'       => $info['action_for'],
                    'icon'      => $info['icon'],
                    'name'      => $info['title'],
                    'isTop'     => $info['isTop'],
                    'children'  => [],
                ];
                break;
            case 'a':
                $menu = [
                    'isTop'     => $info['isTop'],
                    'name'      => $info['title'],
                    'icon'      => $info['icon'],
                    'type'      => $info['type'],
                    'layout'    => $info['layout'],
                    'class'     => $info['class'],
                    'for'       => $info['action_for'],
                    'children'  => [],
                ];
                break;
            case 'submit':
                $menu = [
                    'isTop'     => $info['isTop'],
                    'name'      => $info['title'],
                    'icon'      => $info['icon'],
                    'type'      => $info['type'],
                    'layout'    => $info['layout'],
                    'class'     => $info['class'],
                    'form'      => $info['action_form'],
                    'children'  => [],
                ];
                break;
            case 'button':
                $menu = [
                    'isTop'     => $info['isTop'],
                    'name'      => $info['title'],
                    'icon'      => $info['icon'],
                    'type'      => $info['type'],
                    'layout'    => $info['layout'],
                    'class'     => $info['class'],
                    'id'        => $info['action_id'],
                    'children'  => [],
                ];
                break;
            default :
                $menu = [];

        }
        return $menu;
    }
}