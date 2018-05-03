<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/6
 * Time: 14:11
 */

namespace app\cake\controller;


use app\cake\model\CakeTimelist;
use think\Controller;

class CakeTimelistController extends Controller
{

    /**
     * @return array
     * @throws \think\exception\DbException
     */
    public function time()
    {
        // 时间
        $curTime = date('Y-m-d H:i:s');

        $rec = CakeTimelist::getCurrentExpect($curTime);

        $offset = (strtotime($rec['draw']) - strtotime($rec['open_bet'])) / 60;
        $arr    = array(
            'expect'    => $rec['expect'],
            'endtime'   => strtotime($rec['close_bet']) - strtotime($curTime),
            'opentime'  => strtotime($rec['draw']) - strtotime($curTime),
            'saleNum'   => 11,
            'unsaleNum' => 396 - 11,
            'offset'    => intval($offset),
        );
        return $arr;
    }


}