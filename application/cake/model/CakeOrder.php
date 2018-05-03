<?php

namespace app\cake\model;


class CakeOrder extends Base
{
    /**
     * 查找所有当期未开奖注单
     *
     * @param $expect
     *
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getNotOpenOrdersByExpect($expect)
    {
        return self::all(function($query) use ($expect) {
            $query->where(['expect' => $expect]);
        });
    }

    /**
     * 用户下注历史
     *
     * @param       $page
     * @param       $per_page
     * @param array $where
     *
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function history($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;
        $list = self::all(function($query) use ($start, $per_page, $where) {
            $query->field('tokenint', true)->where($where)->order('id', 'desc')->limit($start, $per_page);
        });
        return $list;
    }


    /**
     * @param       $page
     * @param       $per_page
     * @param array $where
     *
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function summary($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;

        $list = self::field('`lty_name`, `expect`, COUNT(*) AS bet_count, SUM(`open_win`) AS `valid_money`')->where($where)->group('lty_name, expect')->order('expect', 'desc')->limit($start, $per_page)->select();

        return $list;
    }

    /**
     * 指定条件订单列表
     *
     * @param $where
     *
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getOrders($where)
    {
        return self::all(function($query) use ($where) {
            $query->field('tokenint', true)->where($where);
        });
    }

    /**
     * @param $start
     * @param $end
     *
     * @param $tokenint
     *
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function sumData($start, $end, $tokenint)
    {
        return self::field('COUNT(`id`) as order_num, SUM(`money`) as sum_money, SUM(`open_win`) AS win, SUM(`fs_sv`) as fs')
            ->where(['tokenint' => $tokenint])
            ->whereTime('create_time', 'between', [
                $start,
                $end
            ])->find();
    }

    /**
     * @param $expect
     * @param $mark_a
     * @return float|int
     */
    public static function getSumMoneyByExpectAndMarkA($expect, $mark_a)
    {
        return self::where([
            'expect' => $expect,
            'mark_a' => $mark_a,
        ])->sum('money');
    }

    /**
     * 查询未结金额
     *
     * @param array $where
     * @return float|int
     */
    public static function getUnclearMoney($where = [])
    {
        return self::where($where)->where(['open_stu' => 0])->sum('money');
    }

    /**
     * 每个玩法的下注数据统计
     *
     * @param array $where
     * @return void
     */
    public static function getMarkbOrders($where)
    {
        return self::field('SUM(`money`) AS `bet_money`, COUNT(`money`) AS `bet_num`, -SUM(`open_win`) AS `sum_win`, `mark_b`, `mark_a`')
            ->where($where)
            ->group('mark_a, mark_b')
            ->select();
    }
}
