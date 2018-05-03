<?php

namespace app\pk10\model;


class Pk10Timelist extends Base
{
    /**
     * @param $lottery_time
     *
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function getCurrentExpect($lottery_time)
    {
        $expect = self::get(function($query) use ($lottery_time) {
            $query->where([
                'open_bet' => [
                    '<=',
                    $lottery_time
                ],
                'draw'     => [
                    '>=',
                    $lottery_time
                ],
            ])->order('id', 'asc');
        });
        return $expect;
    }
}
