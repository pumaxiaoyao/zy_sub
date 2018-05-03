<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/27
 * Time: 18:03
 */

namespace app\api\controller\admin;


use app\api\model\AccAuth;
use app\auth\controller\AdminBaseController;
use think\Request;

class AccAuthController extends AdminBaseController
{
    /**
     * 修改权限
     * @param Request $request
     * @return mixed
     */
    public function setAdminAuth(Request $request)
    {
        $params = $request->only('user_id,auth_ids');

        $error = $this->validate($params, [
            'user_id'       => 'require|number|gt:0',
            'auth_ids'      => 'require|array'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $user_id = trim($params['user_id']);
        $tokenint = get_user_tokenint_by_id($user_id);

        $checkedAuthIds = AccAuth::getCheckedAuth($tokenint);
        $newAuthIds = $params['auth_ids'];

        $del = array_diff($checkedAuthIds, $newAuthIds);
        $add = array_diff($newAuthIds, $checkedAuthIds);

        // 删除不要的权限
        $delStatus = false;
        if ($del) {
            $delStatus = AccAuth::deleteAuth($tokenint, $del);
        }

        // 添加新权限
        $addStatus = false;
        if ($add) {
            $addStatus = AccAuth::addAuth($user_id, $add);
        }

        if ($delStatus || $addStatus) {
            $this->jsonData['msg'] = '权限修改成功';
            $this->jsonData['data']['checked'] = AccAuth::getCheckedAuth($tokenint);
            return $this->jsonData;
        }
        $this->jsonData['status'] = 0;
        $this->jsonData['msg'] = '权限修改失败';
        return $this->jsonData;
    }


}