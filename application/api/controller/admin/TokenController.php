<?php

namespace app\api\controller\admin;

use app\api\model\AccLogin;
use app\api\model\AccOnline;
use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use GatewayClient\Gateway;
use think\Request;

class TokenController extends AdminBaseController
{

    /**
     * 用户登录接口
     *
     * @param Request $request
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function login (Request $request)
    {
        if ($request->isPost()) {
            $postData = $request->post();
            $user = AccUsers::getUserByName(trim($postData['username']));
            if (!$user) {
                $this->jsonData['status'] = 404;
                $this->jsonData['msg'] = '账号不存在';
                return $this->jsonData;
            }
            if (0 == $user->status) {
                $this->jsonData['status'] = 403;
                $this->jsonData['msg'] = '您没有此操作权限！';
                return $this->jsonData;
            }
            if (0 == $user->type) {
                $this->jsonData['status'] = 403;
                $this->jsonData['msg'] = '您不是管理员，无权登录后台';
                return $this->jsonData;
            }
            if (password_verify(trim($postData['pwd_1']), $user->pwd_1)) {

                $token = $this->genJwtToken($user);

                // 记录登录信息
                AccLogin::add($user);

                // 检查在线表，若已有记录，则先删除，再添加在线记录
                // 否则直接添加
                AccOnline::del($user->tokenint);
                AccOnline::add($user);

                $this->jsonData['data']['user_id'] = $user->id;
                $this->jsonData['data']['token'] = $token;
                return $this->jsonData;
            } else {
                $this->jsonData['status'] = 0;
                $this->jsonData['msg'] = '密码有误';
                return $this->jsonData;
            }
        }
    }

    /**
     * 用户退出登录接口
     * @return array
     */
    public function logout ()
    {

        $user = $this->user;
        $user->tokenext = '';
        $user->isUpdate()->save();

        // 删除在线信息
        $tokenint = $user->tokenint;
        AccOnline::del($tokenint);

        $client_ids = Gateway::getClientIdByUid($tokenint);
        foreach ($client_ids as $client_id) {
            Gateway::unbindUid($client_id, $tokenint);
        }

        cache($tokenint, null);

        $this->jsonData['msg'] = '已退出';
        $this->jsonData['data']['client_ids'] = $client_ids;
        return $this->jsonData;
    }

    /**
     * 刷新令牌
     * @return array
     */
    public function refreshToken ()
    {
        $token = $this->reGenerateToken();
        if (!$token) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '重新生成令牌失败！';
            return $this->jsonData;
        }
        $this->jsonData['msg'] = '重新生成令牌成功！';
        $this->jsonData['data']['token'] = $token;
        return $this->jsonData;
    }
}
