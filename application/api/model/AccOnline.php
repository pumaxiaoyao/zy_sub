<?php

namespace app\api\model;

use think\Model;

class AccOnline extends Model
{
    /**
     * 用户登录添加在线信息
     * @param $user
     */
    public static function add($user)
    {
        $online['tokenint'] = $user->tokenint;
        $online['tokenext'] = $user->tokenext;
        $online['username'] = $user->username;
        $online['nickname'] = $user->nickname;
        $online['loginip'] = request()->ip();
        $online['logintime'] = get_cur_date();
        self::insert($online);
    }

    /**
     * 用户退出删除在线记录
     * @param $tokenint
     */
    public static function del($tokenint)
    {
        self::where(['tokenint' => $tokenint])->delete();
    }

    /**
     * 刷新操作时间
     * @param $tokenint
     */
    public static function updateTime($tokenint)
    {
        self::where(['tokenint' => $tokenint])->update(['logintime' => get_cur_date()]);
    }

    /**
     * 查找所有在线用户
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function allOnlineUsers()
    {
        $validLoginDate = date('Y-m-d H:i:s', time() - KEEP_LOGIN_TIME);
        return self::where(['logintime' => ['>', $validLoginDate]])->select();
    }

    /**
     * 在线人数
     * @return int|string
     */
    public static function onlineNum()
    {
        $validLoginDate = date('Y-m-d H:i:s', time() - KEEP_LOGIN_TIME);
        return self::where(['logintime' => ['>', $validLoginDate]])->count();
    }
}
