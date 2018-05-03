<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/6
 * Time: 14:11
 */

namespace app\cqssc\controller;


use app\cqssc\model\SscTimelist;
use think\Controller;

class SscTimelistController extends Controller
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
        $qs = SscTimelist::getCurrentExpect($lottery_time);


        if ($qs) {
            $expect    = date("Ymd", $lottery_timestamp) . fix_num($qs['expect']);
            $close_bet = strtotime(date("Y-m-d", $lottery_timestamp) . ' ' . $qs['close_bet']) - $lottery_timestamp;
            $draw      = strtotime(date("Y-m-d", $lottery_timestamp) . ' ' . $qs['draw']) - $lottery_timestamp;
        } else {
            $qs = SscTimelist::get(['expect' => 25]);
            if ($qs) {
                $day       = $lottery_timestamp;
                $expect    = date("Ymd", $day) . fix_num($qs['expect']);
                $close_bet = $day - strtotime(date("Y-m-d", $day) . ' ' . $qs['close_bet']);
                $draw      = $day - strtotime(date("Y-m-d", $day) . ' ' . $qs['draw']);
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
            'unsaleNum' => 120 - $qs['id'],
            'offset'    => intval($offset),
        );
        return $timelist;
    }
}