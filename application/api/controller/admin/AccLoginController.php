<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/1
 * Time: 16:47
 */

namespace app\api\controller\admin;


use app\api\model\AccLogin;
use app\auth\controller\AdminBaseController;

class AccLoginController extends AdminBaseController
{
    /**
     * 用户登录记录列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $logins = AccLogin::getMembers();
        $this->jsonData['data']['logins'] = $logins;
        return $this->jsonData;
    }

    /**
     * 指定用户登录记录
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $login = AccLogin::getMember($id);
        $this->jsonData['data']['login'] = $login;
        return $this->jsonData;
    }
}