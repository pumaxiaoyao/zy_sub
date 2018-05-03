<?php

namespace app\api\model;

use think\Model;

class AccMoney extends Model
{
    /**
     * 获取用户资金列表
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getList()
    {
        return self::all(function ($query) {
            $query->field('tokenint', true)->order('id desc');
        });
    }

    /**
     * 查看资金详情
     * @param $tokenint
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getMoneyByUser($tokenint)
    {
        return self::field('tokenint', true)->where(['tokenint' => $tokenint])->find();
    }

    /**
     * 查看资金详情
     * @param $user_id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getMoneyByUserid($user_id)
    {
        $tokenint = AccUsers::getTokenint($user_id);
        return self::getMoneyByUser($tokenint);
    }

    /**
     * @param $id
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function getMoneyById($id)
    {
        return self::get(function ($query) use ($id) {
            $query->field('tokenint', true)->where(['id' => $id]);
        });
    }

    /**
     * 插入一条新用户资金记录
     * @param $user_id
     * @throws \think\exception\DbException
     */
    public static function addMoney($user_id)
    {
        $user = AccUsers::get($user_id);
        $money['username'] = $user->username;
        $money['nickname'] = $user->nickname;
        $money['tokenint'] = $user->tokenint;
        $money['tokenext'] = $user->tokenext;
        $money['cash_money'] = 0.00;
        $money['credit_money'] = 0.00;
        $money['ctime'] = get_cur_date();
        self::insert($money);
    }

    /**
     * 插入一条新用户资金记录
     * @param $user_id
     * @param $post
     * @return int|string
     * @throws \think\exception\DbException
     */
    public static function adminAddMoney($user_id, $post)
    {
        $user = AccUsers::get($user_id);
        $money['username'] = $user->username;
        $money['nickname'] = $user->nickname;
        $money['tokenint'] = $user->tokenint;
        $money['tokenext'] = $user->tokenext;
        $money['cash_money'] = isset($post['cash_money']) ? $post['cash_money'] : 0.00;
        $money['credit_money'] = isset($post['credit_money']) ? $post['credit_money'] : 0.00;
        $money['ctime'] = get_cur_date();
        return self::insertGetId($money);
    }

    /**
     * 查找用户现金额度
     * @param $tokenint
     * @return mixed
     */
    public static function getCashByUser($tokenint)
    {
        return self::where(['tokenint' => $tokenint])->value('cash_money');
    }

    /**
     * 查找用户信用额度
     * @param $tokenint
     * @return mixed
     */
    public static function getCreditByUser($tokenint)
    {
        return self::where(['tokenint' => $tokenint])->value('credit_money');
    }

    /**
     * 初始化一个用户的金额记录
     * @param AccUsers $user
     *
     * @return bool
     */
    public static function initMoney(AccUsers $user)
    {
        $data['username'] = $user->username;
        $data['nickname'] = $user->nickname;
        $data['tokenint'] = $user->tokenint;
        $data['cash_money'] = 0.00;
        $data['credit_money'] = 0.00;
        $data['ctime'] = date('Y-m-d H:i:s');
        $result = self::insert($data);
        if ($result) {
            return true;
        }
        return false;
    }
}
