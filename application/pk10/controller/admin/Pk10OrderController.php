<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/8
 * Time: 9:52
 */

namespace app\pk10\controller\admin;


use think\Request;
use app\pk10\model\Pk10Odds;
use app\pk10\model\Pk10Order;
use app\auth\controller\AdminBaseController;

class Pk10OrderController extends AdminBaseController
{
    /**
     * 用户下注历史
     *
     * @param Request $request
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function history(Request $request)
    {
        $params = $request->param();
        // 页数
        $page = isset($params['page']) ? $params['page'] : 1;
        if ($page < 1) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg']    = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        // 查询条件
        if (isset($params['lucky'])) {
            $where['open_ret'] = $params['lucky'];
        }
        if (isset($params['clear'])) {
            $where['open_stu'] = $params['clear'];
        }
        if (isset($params['range'])) {
            $timeRange = get_time_range($params['range']);
            $where['create_time'] = ['between' , [$timeRange['start'], $timeRange['end']]];
        }

        $where['tokenint'] = [
            'in',
            $this->subUserTokens
        ];
        generate_conditions($where, $params);

        // 下注列表
        $list = Pk10Order::history($page, $per_page, $where);
        $url  = $request->baseUrl();

        $data = paginate_data($page, $per_page, $params, $where, $list, $url, Pk10Order::class, 'history');

        $this->jsonData['data'] = $data;

        return $this->jsonData;
    }

    /**
     * 玩法下注统计
     *
     * @param Request $request
     * @return array
     */
    public function summaryMarkb(Request $request)
    {
        $param = $request->only(['expect', 'range']);
        $error = $this->validate($param, [
            'expect|期号' => 'number',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }
        $where = [];
        if (isset($param['expect'])) {
            if (!Pk10Lottery::expectExists($param['expect'])){
                $this->jsonData['status'] = 0;
                $this->jsonData['msg'] = "期号 " . $param['expect'] . " 不存在";
                return $this->jsonData;
            }
            $where['expect'] = $param['expect'];
            $this->jsonData['data']['expect'] = $param['expect'];
        }
        if (isset($param['range'])) {
            $timeRange = get_time_range($param['range']);
            $where['create_time'] = ['between' , [$timeRange['start'], $timeRange['end']]];
        }
        // 查询所有有下注的玩法统计数据
        $markb = Pk10Order::getMarkbOrders($where);
        // 查询所有玩法
        $markbAll = $this->getAllMarkb();
        // foreach ($markbAll as $key => &$item) {
        //     foreach ($item as $ke => &$val) {
        //         foreach ($markb as $k => $value) {
        //             if ($val['mark_a'] == $value['mark_a'] && $val['mark_b'] == $value['mark_b']) {
        //                 $val['type'] = $val['mark_a'] . '-' . $val['mark_b'];
        //                 $val['bet_num'] = $value['bet_num'];
        //                 $val['bet_money'] = $value['bet_money'];
        //                 $val['sum_win'] = $value['sum_win'];
        //             }
        //         }
        //     }
        // }
        foreach ($markbAll as $key => &$item) {
            $item['bet_num'] = 0;
            $item['bet_money'] = 0;
            $item['sum_win'] = 0;
            $item['type'] = $item['mark_a'] . '-' . $item['mark_b'];
            foreach ($markb as $k => $value) {
                if ($item['mark_a'] == $value['mark_a'] && $item['mark_b'] == $value['mark_b']) {
                    $item['bet_num'] = $value['bet_num'];
                    $item['bet_money'] = $value['bet_money'];
                    $item['sum_win'] = $value['sum_win'];
                }
            }
        }
        $summary['sum_bet_num'] = array_sum(array_column($markbAll, 'bet_num'));
        $summary['sum_bet_money'] = array_sum(array_column($markbAll, 'bet_money'));
        $summary['sum_open_win'] = array_sum(array_column($markbAll, 'sum_win'));
        $this->jsonData['data']['summary'] = $summary;
        $this->jsonData['data']['list'] = $markbAll;
        return $this->jsonData;
    }

    /**
     * 查询所有分类玩法的赔率
     *
     * @return array
     */
    private function getAllMarkb()
    {
        $odds = Pk10Odds::getRealOdds()->toArray();
        $newOdds = [];
        foreach ($odds as $key => $item) {
            $mark_a = $item['mark_a'];
            $mark_b = $item['mark_b'];
            unset($item['id']);
            unset($item['mark_a']);
            unset($item['mark_b']);
            unset($item['bet_limit']);
            unset($item['dec_odds']);

            $newItem = [];
            foreach($item as $k => $v)
            {
                if (floatval($v) <= 0) {
                    continue;
                }
                $tmpItem['mark_a'] = $mark_b;
                $tmpItem['rate'] = $v;
                if ($key >= 0 && $key <= 9) {
                    switch($k) {
                        case 'A':
                            $tmpItem['mark_b'] = 1;
                            break;
                        case 'B':
                            $tmpItem['mark_b'] = 2;
                            break;
                        case 'C':
                            $tmpItem['mark_b'] = 3;
                            break;
                        case 'D':
                            $tmpItem['mark_b'] = 4;
                            break;
                        case 'E':
                            $tmpItem['mark_b'] = 5;
                            break;
                        case 'F':
                            $tmpItem['mark_b'] = 6;
                            break;
                        case 'G':
                            $tmpItem['mark_b'] = 7;
                            break;
                        case 'H':
                            $tmpItem['mark_b'] = 8;
                            break;
                        case 'I':
                            $tmpItem['mark_b'] = 9;
                            break;
                        case 'J':
                            $tmpItem['mark_b'] = 10;
                            break;
                        case 'K':
                            $tmpItem['mark_b'] = '大';
                            break;
                        case 'L':
                            $tmpItem['mark_b'] = '小';
                            break;
                        case 'M':
                            $tmpItem['mark_b'] = '单';
                            break;
                        case 'N':
                            $tmpItem['mark_b'] = '双';
                            break;
                        case 'O':
                            if ($key <= 4) {
                                $tmpItem['mark_b'] = '龙';
                            }
                            break;
                        case 'P':
                            if ($key <= 4) {
                                $tmpItem['mark_b'] = '虎';
                            }
                            break;
                    }
                } elseif ($key === 10) {
                    switch($k) {
                        case 'A':
                            $tmpItem['mark_b'] = 3;
                            break;
                        case 'B':
                            $tmpItem['mark_b'] = 4;
                            break;
                        case 'C':
                            $tmpItem['mark_b'] = 5;
                            break;
                        case 'D':
                            $tmpItem['mark_b'] = 6;
                            break;
                        case 'E':
                            $tmpItem['mark_b'] = 7;
                            break;
                        case 'F':
                            $tmpItem['mark_b'] = 8;
                            break;
                        case 'G':
                            $tmpItem['mark_b'] = 9;
                            break;
                        case 'H':
                            $tmpItem['mark_b'] = 10;
                            break;
                        case 'I':
                            $tmpItem['mark_b'] = 11;
                            break;
                        case 'J':
                            $tmpItem['mark_b'] = 12;
                            break;
                        case 'K':
                            $tmpItem['mark_b'] = 13;
                            break;
                        case 'L':
                            $tmpItem['mark_b'] = 14;
                            break;
                        case 'M':
                            $tmpItem['mark_b'] = 15;
                            break;
                        case 'N':
                            $tmpItem['mark_b'] = 16;
                            break;
                        case 'O':
                            $tmpItem['mark_b'] = 17;
                            break;
                        case 'P':
                            $tmpItem['mark_b'] = 18;
                            break;
                        case 'Q':
                            $tmpItem['mark_b'] = 19;
                            break;
                    }
                } else{
                    switch($k) {                        
                        case 'A':
                            $tmpItem['mark_b'] = '大';
                            break;
                        case 'B':
                            $tmpItem['mark_b'] = '小';
                            break;
                        case 'C':
                            $tmpItem['mark_b'] = '单';
                            break;
                        case 'D':
                            $tmpItem['mark_b'] = '双';
                            break;
                    }
                }
                
                array_push($newItem, $tmpItem);
            }
            // array_push($newOdds, $newItem);
            $newOdds = array_merge($newOdds, $newItem);
        }
        return $newOdds;
    }


}