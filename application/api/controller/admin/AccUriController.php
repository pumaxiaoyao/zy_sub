<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/28
 * Time: 9:54
 */

namespace app\api\controller\admin;


use app\api\model\AccAuth;
use app\api\model\AccUri;
use app\auth\controller\AdminBaseController;
use think\Request;

class AccUriController extends AdminBaseController
{
    /**
     * 用户的权限树
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function authTree(Request $request)
    {
        $params = $request->only('user_id');

        $error = $this->validate($params, [
            'user_id'      => 'require|number|egt:1',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $user_id = trim($params['user_id']);
        $tokenint = get_user_tokenint_by_id($user_id);
        $checkedMenus = AccAuth::getCheckedAuth($tokenint);
        $this->jsonData['data']['menus'] = $this->getMenus();
        $this->jsonData['data']['checked'] = $checkedMenus;
        return $this->jsonData;
    }

    /**
     * 获取权限树
     * @param int $parent_id
     * @return array|bool
     * @throws \think\exception\DbException
     */
    private function getMenus($parent_id = 0)
    {
        $menusArr = [];
        $ids = AccUri::where(['parent_id' => $parent_id])->column('id');
        if (empty($ids)) {
            return [];
        }
        foreach ($ids as $key => $id) {
            $menu = AccUri::get($id);
            $new_menus = $this->getMenus($id);
            if ($new_menus) {
                $menu->child_menus = $new_menus;
            }
            $menusArr[$key] = $menu;
        }
        return $menusArr;
    }


    /**
     * 权限无限级分类
     * @return array|bool
     * @throws \think\exception\DbException
     */
    public function getMenusTree()
    {
        $menus = $this->getSubMenus();
        return $menus;
    }

    /**
     * 递归获取权限列表
     * @param int $parent_id
     * @return array|bool
     * @throws \think\exception\DbException
     */
    private function getSubMenus($parent_id = 0)
    {
        $menusArr = [];
        $ids = AccUri::where(['parent_id' => $parent_id])->column('id');
        if (empty($ids)) {
            return false;
        }
        foreach ($ids as $id) {
            $menu = AccUri::get($id);
            $menuArr['id'] = $menu->id;
            $menuArr['parent_id'] = $menu->parent_id;
            $menuArr['name'] = $menu->name . '（' . $menu->mark . '）';
            $menuArr['uri'] = $menu->uri;
            $menuArr['mark'] = $menu->mark;
            array_push($menusArr, $menuArr);
            $new_menus = $this->getSubMenus($id);
            if ($new_menus) {
                foreach ($new_menus as $new_menu) {
                    $new_menuArr['id'] = $new_menu['id'];
                    $new_menuArr['parent_id'] = $new_menu['parent_id'];
                    $new_menuArr['name'] = "&emsp;&emsp;|— " . $new_menu['name'];
                    $new_menuArr['uri'] = $new_menu['uri'];
                    $new_menuArr['mark'] = $new_menu['mark'];
                    array_push($menusArr, $new_menuArr);
                }
            }
        }
        return $menusArr;
    }
}