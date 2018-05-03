<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/6
 * Time: 14:11
 */

namespace app\pk10\controller;


use app\pk10\model\Pk10Timelist;
use think\Controller;

class Pk10TimelistController extends Controller
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

        //算期数的
        $web_site               = [];
        $web_site['pk10_knum']  = '669963';
        $web_site['pk10_ktime'] = '2018-03-08';
        $fixno                  = intval($web_site['pk10_knum']);
        $qs                     = Pk10Timelist::getCurrentExpect($lottery_time);

        $daynum = floor(($lottery_timestamp - strtotime($web_site['pk10_ktime'] . " 00:00:00")) / 3600 / 24);
        $lastno = ($daynum - 1) * 179 + $fixno;
        if ($qs) {
            $expect    = $lastno + $qs['expect'];
            $close_bet = strtotime(date("Y-m-d", $lottery_timestamp) . ' ' . $qs['close_bet']) - $lottery_timestamp;
            $draw      = strtotime(date("Y-m-d", $lottery_timestamp) . ' ' . $qs['draw']) - $lottery_timestamp;
        } else {
            $qs = Pk10Timelist::get(1);
            if ($qs) {
                $expect    = $lastno + $qs['expect'];
                $close_bet = $lottery_timestamp - strtotime(date("Y-m-d", $lottery_timestamp) . ' ' . $qs['close_bet']);
                $draw      = $lottery_timestamp - strtotime(date("Y-m-d", $lottery_timestamp) . ' ' . $qs['draw']);
            } else {
                $expect    = -1;
                $close_bet = -1;
                $draw      = -1;
            }
        }


        $offset   = (strtotime($qs['draw']) - strtotime($qs['open_bet'])) / 60;
        $timelist = array(
            'expect'    => $expect,
            'endtime'   => $close_bet,
            'opentime'  => $draw,
            'saleNum'   => $qs['id'],
            'unsaleNum' => 179 - $qs['id'],
            'offset'    => intval($offset),
        );
        return $timelist;
    }
}