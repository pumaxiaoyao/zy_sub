<?php

namespace app\api\model;

use think\Model;

class AccMdraw extends Model
{
    /**
     * 所有用户提现记录列表
     *
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getWithdraws($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;
        return self::all(function ($query) use ($start, $per_page, $where) {
            $query->field('tokenint', true)
                ->where($where)
                ->order('tp_time desc, tp_stu')
                ->limit($start, $per_page);
        });
    }
    
    /**
     * 查找指定用户的提现记录列表
     * @param $tokenint
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getWithdrawByUser($tokenint)
    {
        return self::field('tokenint', true)->where(['tokenint' => $tokenint])->order('tp_time desc')->select();
    }

    /**
     * 查找指定提现记录
     * @param $id
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function getWithdrawById($id)
    {
        return self::field('tokenint', true)->find($id);
    }

    /**
     * 删除指定提现记录
     * @param $id
     * @return int
     */
    public static function deleteById($id)
    {
        return self::destroy($id);
    }

    /**
     * 审核用户提现
     * @param $id
     * @param $pass
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function auditWithdraw($id, $pass)
    {
        $withdraw = self::get($id);
        if (is_null($withdraw)) {
            return 404;
        }
        if (1 != $withdraw->tp_stu) {
            return 403;
        }
        $withdraw->tp_stu = $pass;
        $res = $withdraw->save();
        if ($res) {
            return 201;
        } else {
            return 0;
        }
    }

    /**
     * 查找提现记录对应的tokenint
     * @param $id
     * @return mixed
     */
    public static function getTokenintById($id)
    {
        return self::where(['id' => $id])->value('tokenint');
    }

    /**
     * 用户提现
     * @param $withdraw
     * @return int|string
     */
    public static function addWithdraw($withdraw)
    {
        return self::insertGetId($withdraw);
    }
}
