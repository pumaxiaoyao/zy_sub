<?php

namespace app\api\model;

use think\Db;
use think\helper\Time;
use think\Model;

class AccMychg extends Model
{
    /**
     * 查找指定用户的资金变动记录
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getChgsByUser($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;

        $subSql = self::field('`id`, `username`, `nickname`, `type`, `old`,
                IF(`type` in (2,3), -`chg`, `chg`) AS `chg`, `cur`, `con`, `opr_nickname`,
                `opr_username`, `opr_ip`, `opr_time`, `opr_mark`')->where($where)->buildSql();
        $list = Db::table($subSql . ' a')
            ->order('id', 'desc')
            ->limit($start, $per_page)
            ->select();

        return $list;
    }

    /**
     * 今日盈亏
     * @param $where
     * @return float|int
     * @throws \think\exception\DbException
     */
    public static function todayYk($where)
    {
        list($start, $end) = Time::today();
        $where['opr_time'] = ['between', [date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]];
        $where['type'] = ['in', [CHG_BET, CHG_BET_LUCKY, CANCEL_ORDER]];
        $subSql = self::field('IF(`type` = 3, -`chg`, `chg`) as `chg`')
            ->where($where)
            ->buildSql();
        $yk = Db::table($subSql . ' a')->sum('`chg`');
        return $yk;
    }

    /**
     * 查找用户最后一次资金变动记录
     * @param $tokenint
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLastChgByUser($tokenint)
    {
        return self::where(['tokenint' => $tokenint])->order('id desc')->find();
    }

    /**
     * @param $page
     * @param $per_page
     * @param $where
     *
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getAll($page, $per_page, $where)
    {
        $start = ($page - 1) * $per_page;
        return self::all(function($query) use ($where, $start, $per_page) {
            $query->field('tokenint', true)
                ->where($where)
                ->order('id', 'desc')
                ->limit($start, $per_page);
        });
    }

    /**
     * 今日返水
     * @param $where
     * @return float|int
     */
    public static function todayFs($where)
    {
        $where['type'] = 5;
        $where['opr_time'] = ['like', date('Y-m-d') . '%'];
        return self::where($where)->sum('chg');
    }
}
