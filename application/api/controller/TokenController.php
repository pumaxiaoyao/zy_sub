<?php

namespace app\api\controller;

use app\api\model\AccLogin;
use app\api\model\AccOnline;
use app\api\model\AccUsers;
use app\auth\controller\BaseController;
use think\Request;

class TokenController extends BaseController
{
    /**
     * 用户登录接口
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function login(Request $request)
    {
        if ($request->isPost()) {
            $postData = $request->post();
            $user = AccUsers::getUserByName(trim($postData['username']));
            if (!$user) {
                return ['status' => 404, 'msg' => '账号不存在'];
            }
            if (0 == $user->status) {
                return ['status' => 403, 'msg' => '您没有此操作权限！'];
            }
            if (!$user) {
                return ['status' => 0, 'msg' => '用户名有误！'];
            }
            // 验证用户密码
            if (password_verify(trim($postData['pwd_1']), $user->pwd_1)) {
                $token = $this->genJwtToken($user);

                $user->tokenext = $token;
                $user->isUpdate()->save();

                // 记录登录信息
                AccLogin::add($user);

                // 检查在线表，若已有记录，则先删除，再添加在线记录
                // 否则直接添加
                AccOnline::del($user->tokenint);
                AccOnline::add($user);

                return ['status' => 200, 'msg' => '登录成功', 'data' => [
                    'user_id' => $user->id,
                    'token' => $token
                ]];
            } else {
                return ['status' => 0, 'msg' => '密码有误'];
            }
        }
    }

    /**
     * 用户退出登录接口
     * @return array
     */
    public function logout()
    {
//        $user_id = $this->payload['user_id'];
        $user = $this->user;

        $user->tokenext = '';
        $user->isUpdate()->save();

        // 删除在线信息
        AccOnline::del($user->tokenint);

        cache($user->tokenint, null);
//        cookie('jwt', null);
        return ['status' => 200, 'msg' => 'logout success'];
    }

    /**
     * 刷新令牌
     * @return array
     */
    public function refreshToken()
    {
        $token = $this->reGenerateToken();
        if (!$token) {
            return ['status' => 0, 'msg' => '重新生成令牌失败！'];
        }
//        cookie('jwt', $token, ['expire' => KEEP_LOGIN_TIME]);
        return ['status' => 200, 'msg' => '重新生成令牌成功！', 'data' => [
            'token' => $this->token
        ]];
    }
}
