<?php

namespace app\api\model;

use think\Model;

class AccLogin extends Model
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
     * 查找所有用户登录记录
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getMembers()
    {
        return self::field('id, username, nickname, loginip, logintime')
            ->order('id desc')
            ->select();
    }

    /**
     * 查找指定用户的登录记录
     * @param $user_id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getMember($user_id)
    {
        $tokenint = AccUsers::getTokenint($user_id);
        return self::field('id, username, nickname, loginip, logintime')
            ->where(['tokenint' => $tokenint])
            ->order('id desc')
            ->select();
    }

    /**
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLogins($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;
        return self::field('id, username, nickname, loginip, logintime')
            ->where($where)
            ->order('id', 'desc')
            ->limit($start, $per_page)
            ->select();
    }
}
