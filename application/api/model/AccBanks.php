<?php

namespace app\api\model;

use think\Model;

class AccBanks extends Model
{
    /**
     * 用户绑定的所有账户列表
     * @param $tokenint
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function banks($tokenint)
    {
        return self::field('id, username, nickname, realname, bankname, bknumber, address')
            ->where(['tokenint' => $tokenint])
            ->select();
    }

    /**
     * 查找指定绑定信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function bank($id)
    {
        return self::field('id, username, nickname, realname, bankname, bknumber, address')
            ->find($id);
    }

    /**
     * 根据 id 删除绑定账号
     * @param array $ids
     * @return int
     */
    public static function delById($ids)
    {
        return self::where('id', 'in', $ids)
            ->delete();
    }

    /**
     * 清空用户所有绑定的账号
     * @param $tokenint
     * @return int
     */
    public static function delByUser($tokenint)
    {
        return self::where('tokenint', '=', $tokenint)
            ->delete();
    }

    /**
     * 添加绑定账号
     * @param $post
     * @param $user
     * @return int|string
     */
    public static function addBank($post, $user)
    {
        $bank['tokenint'] = $user->tokenint;
        $bank['username'] = $user->username;
        $bank['nickname'] = $user->nickname;
        $bank['realname'] = $post['realname'];
        $bank['bankname'] = $post['bankname'];
        $bank['bknumber'] = $post['bknumber'];
        $bank['address'] = isset($post['address']) ? $post['address'] : '';
        return self::insert($bank);
    }

    /**
     * 修改绑定账号信息
     * @param $post
     * @param $id
     * @return false|int
     * @throws \think\exception\DbException
     */
    public static function updBank($post, $id)
    {
        $bank = self::get($id);
        if (isset($post['bankname'])) {
            $bank->bankname = $post['bankname'];
        }
        if (isset($post['realname'])) {
            $bank->realname = $post['realname'];
        }
        if (isset($post['bknumber'])) {
            $bank->realname = $post['bknumber'];
        }
        if (isset($post['address'])) {
            $bank->realname = $post['address'];
        }
        return $bank->isUpdate()->save();
    }

    /**
     * 查找充值记录对应的tokenint
     * @param $id
     * @return mixed
     */
    public static function getTokenintById($id)
    {
        return self::where(['id' => $id])->value('tokenint');
    }
}
