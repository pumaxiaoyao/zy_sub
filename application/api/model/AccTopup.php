<?php

namespace app\api\model;

use think\Model;

class AccTopup extends Model
{
    /**
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('AccUsers', 'tokenint', 'tokenint');
    }

    /**
     * 所有用户充值记录列表
     *
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getTopups($page, $per_page, $where = [])
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
     * 查找指定用户的充值记录列表
     * @param $tokenint
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getTopupByUser($tokenint)
    {
        return self::field('tokenint', true)->where(['tokenint' => $tokenint])->order('tp_time desc')->select();
    }

    /**
     * 查找指定充值记录
     * @param $id
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function getTopupById($id)
    {
        return self::field('tokenint', true)->find($id);
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

    /**
     * 删除指定充值记录
     * @param $id
     * @return int
     */
    public static function deleteById($id)
    {
        return self::destroy($id);
    }

    /**
     * 审核用户充值
     * @param $id
     * @param $pass
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function auditTopup($id, $pass)
    {
        $topup = self::get($id);
        if (is_null($topup)) {
            return 404;
        }
        if (1 != $topup->tp_stu) {
            return 403;
        }
        $topup->tp_stu = $pass;
        $res = $topup->save();
        if ($res) {
            return 200;
        } else {
            return 0;
        }
    }


    /**
     * 用户充值
     * @param $topup
     * @return int|string
     */
    public static function addTopup($topup)
    {
        return self::insertGetId($topup);
    }

}
