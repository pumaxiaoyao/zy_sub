<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/2/26
 * Time: 17:14
 */

namespace app\auth\controller;


use app\api\model\AccOnline;
use app\api\model\AccUsers;
use Firebase\JWT\JWT;
use think\Config;
use think\Controller;
use Exception;

class BaseController extends Controller
{
    protected $payload;

    protected $user;

    protected $token;

    protected $userTrad;

    protected $jsonData;

    /**
     *
     */
    protected function _initialize ()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Config::load(APP_PATH . 'auth/config.php');
        $this->auth();
        $this->jsonData = ['status' => '200', 'msg' => 'success', 'data' => []];
    }


    /**
     * 用户认证
     * @return bool|mixed
     */
    protected function auth ()
    {
        try {
            $uri = request()->controller() . '/' . request()->action();
            $whiteList = \config('base_white_list');
            if (in_array($uri, $whiteList)) {
                return true;
            }

            $token = get_token(request());
            $this->token = $token;
            if (empty($token)) {
                exit(json_encode(['status' => 403, 'msg' => '您已退出登录，请重新登录']));
            }
            $key = config('jwt_key');

            $obj_token = JWT::decode($token, $key, array('HS256'));
            $payload = json_decode(json_encode($obj_token), true);
            $user_id = $payload['user_id'];
            $user = AccUsers::get($user_id);
            $tokenint = $user->tokenint;

            if (0 == $user->status) {
                // 删除在线信息
                AccOnline::del($tokenint);

                cache($token, null);
                exit(json_encode(['status' => 403, 'msg' => '您被管理员加入黑名单，将被强制退出！']));
            }

            $cache_token = cache($tokenint);
            if ($cache_token != $token) {
                exit(json_encode(['status' => 0, 'msg' => '您已在别处登录']));
            }

            // 刷新 online 表的操作时间
            AccOnline::updateTime($tokenint);
            $this->user = $user;
            $this->payload = $payload;
        } catch (Exception $e) {
            exit(json_encode(['status' => 0, 'msg' => $e->getMessage()]));
        }
    }

    public function checkLogin ()
    {
        exit(json_encode(['status' => 200, 'msg' => '您已登录']));
    }

    /**
     * 生成JWT token
     *
     * @param $user
     *
     * @return string
     */
    protected function genJwtToken ($user)
    {
        $key = config('jwt_key');
        $payload = [
            'user_id' => $user->id,
            'username' => $user->username,
            'exp' => time() + KEEP_LOGIN_TIME,
        ];
        $token = JWT::encode($payload, $key);
        $user->tokenext = $token;
        $user->isUpdate()->save();
        cache($user->tokenint, $token, time() + KEEP_LOGIN_TIME);
        return $token;
    }

    /**
     * 刷新令牌
     * @return bool|string
     */
    protected function reGenerateToken ()
    {
        try {
            $token = $this->token;
            $key = config('jwt_key');
            $obj_token = JWT::decode($token, $key, array('HS256'));
            $arr = json_decode(json_encode($obj_token), true);
            if (is_array($arr)) {
                $user = AccUsers::get($arr['user_id']);
                cache($user->tokenint, $token, time());
                $new_token = $this->genJwtToken($user);
                return $new_token;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 检查查看记录的人和被查看的是不是同一个人
     * 普通用户不能查看其他用户的充值记录
     *
     * @param $payload
     * @param $tokenint
     *
     * @return bool
     */
    protected function checkPermission ($payload, $tokenint)
    {
        $user_id = $payload['user_id'];
        $token_int = AccUsers::getTokenint($user_id);
        if ($token_int === $tokenint) {
            return true;
        } else {
            return false;
        }
    }
}
