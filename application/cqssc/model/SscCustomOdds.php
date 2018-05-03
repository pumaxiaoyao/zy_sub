<?php

namespace app\cqssc\model;


class SscCustomOdds extends Base
{
    /**
     * 检查用户是否有指定赔率表的定制赔率
     *
     * @param $table
     * @param $tokenint
     *
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function isExists($table, $tokenint)
    {
        $result = self::get(function($query) use ($table, $tokenint) {
            $query->where([
                'base_odds' => $table,
                'tokenint'  => $tokenint
            ]);
        });
        if (is_null($result)) {
            return false;
        }
        return true;
    }

    /**
     * @param $table
     * @param $tokenint
     *
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function getOneByTableAndUser($table, $tokenint)
    {
        return self::get(function($query) use ($table, $tokenint) {
            $query->where([
                'tokenint' => $tokenint,
                'base_odds' => $table
            ]);
        });
    }

    /**
     * @param $table
     * @param $tokenint
     *
     * @return mixed
     */
    public static function getOddsByTableAndUser($table, $tokenint)
    {
        $odds = self::where([
            'base_odds' => $table,
            'tokenint'  => $tokenint
        ])->value('odds');
        return json_decode($odds, true);
    }

    /**
     * @param $table
     * @param $tokenint
     *
     * @param $index
     *
     * @return mixed
     */
    public static function getFsByTableAndUser($table, $tokenint, $index)
    {
        $odds = self::getOddsByTableAndUser($table, $tokenint);
        $fs = $odds[$index]['A'];
        return $fs;
    }

    /**
     * 删除用户自定义赔率和返水数据
     * @param $tokenint
     *
     * @return int
     */
    public static function delOneByUser($tokenint)
    {
        return self::where(['tokenint' => $tokenint])->delete();
    }
}
