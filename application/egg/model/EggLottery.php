<?php

namespace app\egg\model;


class EggLottery extends Base
{
    /**
     * 检查指定期数开奖结果是否存在
     * @param $expect
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function expectExists($expect)
    {
        $res = self::get(function ($query) use ($expect) {
            $query->where(['expect' => $expect]);
        });
        if (is_null($res)) {
            return false;
        }
        return true;
    }

    /**
     * 检查本期是否已经开奖
     * @param $expect
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function expectLotteried($expect)
    {
        if (self::expectExists($expect)) {
            $result = self::where(['expect' => $expect])->value('is_lottery');
            if ($result == 1) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 最新一期开奖情况
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function getLastest()
    {
        return self::get(function ($query) {
            $query->order('expect', 'desc');
        });
    }

    /**
     * 开奖历史
     * @param $page
     * @param $per_page
     * @param $where
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function history($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;
        $list = self::all(function ($query) use ($start, $per_page, $where) {
            $query->where($where)
                ->order('expect', 'desc')
                ->limit($start, $per_page);
        });
        return $list;
    }

    /**
     * @param $expect
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function getByExpect($expect)
    {
        return self::get(function ($query) use ($expect) {
            $query->where(['expect' => $expect]);
        });
    }

    /**
     * 查询当天所有开奖数据
     *
     * @return array
     */
    public static function getToday()
    {
        $date = date('Y-m-d');
        $where = [];
        $where['opentime'] = ['like', $date . "%"];
        $list = self::all(function ($query) use ($where) {
            $query->where($where)->order('expect', 'desc');
        });
        return $list;
    }

}
