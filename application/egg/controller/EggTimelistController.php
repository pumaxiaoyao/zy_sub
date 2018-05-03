<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/6
 * Time: 14:11
 */

namespace app\egg\controller;


use app\egg\model\EggTimelist;
use think\Controller;

class EggTimelistController extends Controller
{
    /**
     * 时间管理
     * @return array|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function time()
    {
        $lottery_timestamp = time();
        $lottery_time      = date('H:i:s', $lottery_timestamp);

        //开始读取期数
        $qs = EggTimelist::getCurrentExpect($lottery_time);

        $fixno   = 876121;
        $fixDate = strtotime("2018-03-09 00:00:00");
        $daynum  = floor(($lottery_timestamp - $fixDate) / 3600 / 24);
        $lastno  = ($daynum - 1) * 179 + $fixno - 1;

        if ($qs) {
            $expect    = $lastno + $qs['expect'];
            $close_bet = strtotime(date("Y-m-d", $lottery_timestamp) . ' ' . $qs['close_bet']) - $lottery_timestamp;
            $draw      = strtotime(date("Y-m-d", $lottery_timestamp) . ' ' . $qs['draw']) - $lottery_timestamp;
        } else {
            $expect    = $lastno + 1;
            $close_bet = time() - strtotime(date('Y-m-d 09:04:00'));
            $draw      = time() - strtotime(date('Y-m-d 09:00:00'));
        }

        $offset   = (strtotime($qs['draw']) - strtotime($qs['open_bet'])) / 60;
        $timelist = array(
            'expect'    => $expect,
            'endtime'   => $close_bet,
            'opentime'  => $draw,
            'saleNum'   => $qs['id'],
            'unsaleNum' => 120 - $qs['id'],
            'offset'    => intval($offset),
        );
        return $timelist;
    }
}