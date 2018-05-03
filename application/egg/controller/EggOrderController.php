<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/7
 * Time: 9:47
 */

namespace app\egg\controller;

use app\api\model\AccMoney;
use app\api\model\AccMychg;
use app\api\model\AccUsers;
use app\auth\controller\BaseController;
use app\egg\model\EggCustomOdds;
use app\egg\model\EggOdds;
use app\egg\model\EggOrder;
use app\egg\model\EggRatelist;
use app\egg\model\EggUser;
use GatewayClient\Gateway;
use GuzzleHttp\Client;
use think\Config;
use think\Request;

class EggOrderController extends BaseController
{
    /**
     * @throws \think\exception\DbException
     */
    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Config::load(APP_PATH . 'egg/config.php');
        $this->userTrad = EggUser::getByUser($this->user->tokenint);
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws \think\db\exception\BindParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function save(Request $request)
    {
        $post = $request->only([
            'bets',
            'odds_table'
        ], 'post');

        $error = $this->validate($post, [
            'bets'       => 'require',
            'odds_table' => 'require',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg']    = $error;
            return $this->jsonData;
        }

        // PC蛋蛋当前闭盘、开奖时间、期数
        $eggTime = get_cur_timelist('egg');
        // 比对时间，闭盘则不能下注
        if ($eggTime['endtime'] <= 0) {
            return [
                'status' => 0,
                'msg'    => $eggTime['expect'] . '期已闭盘，下注失败！'
            ];
        }
        $create_time = time();
        $update_time = $create_time;

        $oddsTable   = 'egg_' . trim($post['odds_table']);
        $existTables = EggRatelist::where(['tokenint' => $this->user->tokenint])->column('ratewin_set');
        if (empty($existTables)) {
            return [
                'status' => 0,
                'msg'    => '没有权限下注'
            ];
        } elseif (!in_array($oddsTable, $existTables)) {
            $oddsTable = EggRatelist::where([
                'tokenint' => $this->user->tokenint,
                'sel'      => 1
            ])->value('ratewin_set');
            if (!$oddsTable) {
                $oddsTable = $existTables[0];
            }
        }

        $bets = $post['bets'];
        // 计算总共下注多少钱，与余额及信用额度比较，不足则下注失败
        $sumBetMoney = array_sum(array_column($bets, 'money'));
        $userMoney   = AccMoney::getMoneyByUserid($this->user->id);
        if ($sumBetMoney > ($userMoney->cash_money + $userMoney->credit_money)) {
            return [
                'status' => 0,
                'msg'    => '下注失败，您的余额与信用额度不足，请先充值'
            ];
        }
        $orders         = [];
        $order_nos      = [];
        $tradList       = [];
        $websocket_data = [];
        $expect_orders  = [];
        foreach ($bets as $key => $bet) {
            $bet['expect'] = $eggTime['expect'];
            $order = $this->generateOrderData($expect_orders, $bet, $oddsTable);
            if (count($order) <= 3) {
                return $order;
            }
            $order['expect'] = $eggTime['expect'];
            // 订单状态 0下注成功
            $order['status']      = 0;
            $order['create_time'] = $create_time;
            $order['update_time'] = $update_time;
            array_push($orders, $order);
            array_push($order_nos, $order['order_no']);

            // 组装数据发送给前端页面
            $websocket_data[$key]['nickname']    = $order['nickname'];
            $websocket_data[$key]['lty_name']    = $order['lty_name'];
            $websocket_data[$key]['mark_a']      = $order['mark_a'];
            $websocket_data[$key]['mark_b']      = $order['mark_b'];
            $websocket_data[$key]['money']       = $order['money'];
            $websocket_data[$key]['create_time'] = $order['create_time'];
            $websocket_data[$key]['order_no']    = $order['order_no'];
            $websocket_data[$key]['expect']      = $order['expect'];
            $websocket_data[$key]['rate']        = $order['rate'];
            $websocket_data[$key]['win']         = $order['win'];
            $websocket_data[$key]['fs_sv']       = $order['fs_sv'];
            $websocket_data[$key]['fs_av']       = $order['fs_av'];
            $websocket_data[$key]['fs_mv']       = $order['fs_mv'];
            $websocket_data[$key]['fs_gv']       = $order['fs_gv'];
            $websocket_data[$key]['ls_sv']       = $order['ls_sv'];
            $websocket_data[$key]['ls_av']       = $order['ls_av'];
            $websocket_data[$key]['ls_mv']       = $order['ls_mv'];
            $websocket_data[$key]['ls_gv']       = $order['ls_gv'];

            // 判断是否满足转盘条件
            if (1 == $order['trad_stu']) {
                $trad          = $bet;
                $trad['money'] = $this->userTrad->trad_rate * $trad['money'];
                array_push($tradList, $trad);
            }
        }

        $orderModel = new EggOrder();
        $res        = $orderModel->saveAll($orders);
        if ($res) {
            // 增加资金变动
            $user                       = $this->user;
            $chg_column['tokenint']     = $user->tokenint;
            $chg_column['username']     = $user->username;
            $chg_column['nickname']     = $user->nickname;
            $chg_column['c_type']       = CHG_BET;
            $chg_column['c_old']        = $userMoney->cash_money;
            $chg_column['chg']          = $sumBetMoney;
            $chg_column['cur']          = $chg_column['c_old'] - $chg_column['chg'];
            $chg_column['con']          = 'PC蛋蛋下注';
            $chg_column['opr_tokenint'] = '';
            $chg_column['opr_nickname'] = '';
            $chg_column['opr_username'] = '';
            $chg_column['opr_ip']       = request()->ip();
            $chg_column['opr_time']     = get_cur_date();
            $chg_column['opr_mark']     = '订单号：' . implode(',', $order_nos);
            $chg_id                     = add_chg($chg_column);

            $chg = AccMychg::get($chg_id);
            // 更新用户余额
            upd_money($chg->tokenint, $chg->cur);

            $client_ids = get_all_admin_client_ids($user);
            $msg        = [
                'type'     => 'orders',
                'msg'      => $websocket_data,
                'lty_name' => 'pcegg',
            ];
            Gateway::sendToAll(json_encode($msg), $client_ids);

            // 转盘
            if (!empty($tradList)) {
                $trad                 = EggUser::getByUser($this->user->tokenint);
                $pushData['tokensup'] = $trad->trad_tokensup;
                $pushData['bets']     = $tradList;
                $client               = new Client();
                $response             = $client->post($trad->trad_url, [
                    'form_params' => $pushData
                ]);
                $res                  = \GuzzleHttp\json_decode($response->getBody()->getContents());
                if (200 == $res->status) {
                    return $res;
                }
            }


            return [
                'status' => 200,
                'msg'    => '下注成功'
            ];
        }
        return [
            'status' => 0,
            'msg'    => '下注失败'
        ];
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function history(Request $request)
    {
        $params   = $request->only(['page', 'per_page', 'clear', 'lucky', 'range']);
        $tokenint = $this->user->tokenint;
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
        $where['tokenint'] = $tokenint;
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

        // 下注列表
        $list = EggOrder::history($page, $per_page, $where);
        $url  = $request->baseUrl();

        $data = paginate_data($page, $per_page, $params, $where, $list, $url, EggOrder::class, 'history');

        $this->jsonData['data']   = $data;

        return $this->jsonData;
    }

    /**
     * @param Request $request
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function summary(Request $request)
    {
        $params   = $request->param();
        $tokenint = $this->user->tokenint;
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
        $where['tokenint'] = $tokenint;
        $type              = summary_cond($params, $where);
        $list              = EggOrder::summary($page, $per_page, $where);

        foreach ($list as &$item) {
            $item['detail_url'] = url('/egg/detail', [
                'expect' => $item['expect'],
                'type'   => $type
            ]);
        }

        $url  = $request->baseUrl();
        $data = paginate_data($page, $per_page, $params, $where, $list, $url, EggOrder::class, 'summary');

        $this->jsonData['status'] = 200;
        $this->jsonData['msg']    = 'success';
        $this->jsonData['data']   = $data;

        return $this->jsonData;
    }

    /**
     * @param Request $request
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function detail(Request $request)
    {
        $params   = $request->param();
        $tokenint = $this->user->tokenint;
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
        $where['tokenint'] = $tokenint;
        if (!isset($params['expect'])) {
            return [
                'status' => 0,
                'msg'    => '缺少 expect 参数'
            ];
        }
        $where['expect'] = $params['expect'];
        $list            = EggOrder::history($page, $per_page, $where);
        $url             = $request->baseUrl();
        $data            = paginate_data($page, $per_page, $params, $where, $list, $url, EggOrder::class, 'history');

        $this->jsonData['status'] = 200;
        $this->jsonData['msg']    = 'success';
        $this->jsonData['data']   = $data;

        return $this->jsonData;
    }

    /**
     * 组织生成订单信息
     *
     * @param $expect_orders
     * @param      $bet
     * @param null $panTable
     * @param null $user_id
     *
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function generateOrderData(&$expect_orders, $bet, $panTable = null, $user_id = null)
    {
        if (is_null($user_id)) {
            $user = $this->user;
        } else {
            $user = AccUsers::get($user_id);
        }
        $order['username'] = $user->username;
        $order['nickname'] = $user->nickname;
        $order['tokenint'] = $user->tokenint;
        $order['money']    = $bet['money'];

        // 检查用户注额设置
        $userTrad = $this->userTrad;
        if ($bet['money'] < $userTrad->money_min) {
            return [
                'status' => 0,
                'msg'    => '下注失败，单注下注额不足最小下注限额'
            ];
        }
        if ($bet['money'] > $userTrad->money_max) {
            return [
                'status' => 0,
                'msg'    => '下注失败，单注下注额超出最大下注限额'
            ];
        }

        $order['order_no'] = order_no();
        usleep(mt_rand(1, 10));
        $order['lty_name'] = 'PC蛋蛋';

        // 解析玩法和下注内容
        $content         = $this->handleKey(trim($bet['content']));
        $type            = $content['type'];
        $column          = $content['column'];
        $mark_b          = \config('egg_type')[$type];
        $order['mark_a'] = $mark_b;
        $order['mark_b'] = $this->getBetContent($type, $column);

        // 赔率
        $odds_id = $this->getOddsId($mark_b);
        if ($odds_id === 0) {
            return [
                'status' => 0,
                'msg'    => '下注失败！'
            ];
        }
        if (is_null($panTable)) {
            $pan      = EggRatelist::getPanByUser($user->tokenint);
            $panTable = 'egg_' . strtolower($pan);
        }

        // 检查用户该赔率表是否有定制赔率
        $customExists = EggCustomOdds::isExists($panTable, $user->tokenint);
        if ($customExists) {
            $odds = EggCustomOdds::getOddsByTableAndUser($panTable, $user->tokenint);
        } else {
            $odds = EggOdds::getAll($panTable)->toArray();
        }

        $result = compare_bet_money(EggOrder::class, $odds, $bet, $order, $odds_id, $mark_b, $expect_orders);
        if (true !== $result) {
            return $result;
        }

        $rate = $odds[$odds_id - 1][$column];

        // 判断赔率是否跌倍
        $dec_odds = $odds[$odds_id - 1]['dec_odds'];
        if ($bet['money'] > $dec_odds[1]['limit']) {
            // 第二级跌倍
            $rate -= $dec_odds[1]['dec_odds'];
        } elseif ($bet['money'] > $dec_odds[0]['limit']) {
            // 第一级跌倍
            $rate -= $dec_odds[0]['dec_odds'];
        }
        // 默认不跌倍

        //        $rate = EggOdds::getRate($panTable, $odds_id, $column);
        $order['rate'] = $rate;
        $win           = $rate * $bet['money'];
        if ($win > $userTrad->money_win) {
            return [
                'status' => 0,
                'msg'    => '下注失败！预赢金额超标'
            ];
        }
        $order['win'] = $win;

        // 各级返点和返水

        //        $fs_g = EggOdds::getFs($panTable, 1);
        //        $fs_m = EggOdds::getFs($panTable, 2);
        //        $fs_a = EggOdds::getFs($panTable, 3);
        //        $fs_s = EggOdds::getFs($panTable, 4);
        $fs_g           = $odds[0]['e1'];
        $fs_m           = $odds[1]['e1'];
        $fs_a           = $odds[2]['e1'];
        $fs_s           = $odds[3]['e1'];
        $order['fs_g']  = $fs_g;
        $order['fs_m']  = $fs_m;
        $order['fs_a']  = $fs_a;
        $order['fs_s']  = $fs_s;
        $fs_gv          = $fs_g * $order['money'];
        $fs_mv          = $fs_m * $order['money'];
        $fs_av          = $fs_a * $order['money'];
        $fs_sv          = $fs_s * $order['money'];
        $order['fs_gv'] = $fs_gv;
        $order['fs_mv'] = $fs_mv;
        $order['fs_av'] = $fs_av;
        $order['fs_sv'] = $fs_sv;

        // 各级流水

        //        $ls_g = EggOdds::getLs($panTable, 1);
        //        $ls_m = EggOdds::getLs($panTable, 2);
        //        $ls_a = EggOdds::getLs($panTable, 3);
        //        $ls_s = EggOdds::getLs($panTable, 4);
        $ls_g           = $odds[0]['e2'];
        $ls_m           = $odds[1]['e2'];
        $ls_a           = $odds[2]['e2'];
        $ls_s           = $odds[3]['e2'];
        $order['ls_g']  = $ls_g;
        $order['ls_m']  = $ls_m;
        $order['ls_a']  = $ls_a;
        $order['ls_s']  = $ls_s;
        $ls_gv          = $ls_g * $order['money'];
        $ls_mv          = $ls_m * $order['money'];
        $ls_av          = $ls_a * $order['money'];
        $ls_sv          = $ls_s * $order['money'];
        $order['ls_gv'] = $ls_gv;
        $order['ls_mv'] = $ls_mv;
        $order['ls_av'] = $ls_av;
        $order['ls_sv'] = $ls_sv;

        // 开奖号码
        $order['open_code'] = '';
        // 输赢额
        $order['open_win'] = 0;
        // 是否结算
        $order['open_stu'] = 0;
        // 是否中奖
        $order['open_ret'] = 0;

        // 判断是否满足转盘条件，满足则标记为转盘订单
        $ifTrad = $bet['money'] > $userTrad->trad_max || $win > $userTrad->trad_win;
        if ($ifTrad && $userTrad->trad_url) {
            $order['trad_stu'] = 1;
            $order['trad_val'] = $bet['money'] * $userTrad->trad_rate;
            ;
            $order['trad_url']      = $userTrad->trad_url;
            $order['trad_tokensup'] = $userTrad->tokensup;
            $order['trad_return']   = '';
        } else {
            $order['trad_stu']      = 0;
            $order['trad_val']      = 0;
            $order['trad_url']      = '';
            $order['trad_tokensup'] = '';
            $order['trad_return']   = '';
        }

        // 删除一些不需要的字段
        unset($order['content']);

        return $order;
    }

    /**
     * 根据玩法获取赔率表对应id
     *
     * @param $mark_b
     *
     * @return int
     */
    private function getOddsId($mark_b)
    {
        switch ($mark_b) {
            case '特码':
                $id = 5;
                break;
            case '两面':
                $id = 6;
                break;
            case '波色':
                $id = 7;
                break;
            case '豹子':
                $id = 8;
                break;
            case '特码三压一':
                $id = 9;
                break;
            default:
                $id = 0;
        }
        return $id;
    }

    /**
     * 拆分字段为玩法和下注内容
     *
     * @param $content
     *
     * @return array
     */
    private function handleKey($content)
    {
        $res         = explode('__', $content);
        $type        = $res[0];
        $bet_content = $res[1];
        return [
            'type'   => $type,
            'column' => $bet_content
        ];
    }

    /**
     * 根据玩法和字段来获取下注选项
     *
     * @param $type
     * @param $column
     *
     * @return mixed
     */
    private function getBetContent($type, $column)
    {
        $arr = [];
        switch ($type) {
            case 'ball_1':
                $arr = [
                    'e1'  => '0',
                    'e2'  => '1',
                    'e3'  => '2',
                    'e4'  => '3',
                    'e5'  => '4',
                    'e6'  => '5',
                    'e7'  => '6',
                    'e8'  => '7',
                    'e9'  => '8',
                    'e10' => '9',
                    'e11' => '10',
                    'e12' => '11',
                    'e13' => '12',
                    'e14' => '13',
                    'e15' => '14',
                    'e16' => '15',
                    'e17' => '16',
                    'e18' => '17',
                    'e19' => '18',
                    'e20' => '19',
                    'e21' => '20',
                    'e22' => '21',
                    'e23' => '22',
                    'e24' => '23',
                    'e25' => '24',
                    'e26' => '25',
                    'e27' => '26',
                    'e28' => '27',
                ];
                break;
            case 'ball_2':
                $arr = [
                    'e1'  => '大',
                    'e2'  => '小',
                    'e3'  => '单',
                    'e4'  => '双',
                    'e5'  => '大单',
                    'e6'  => '大双',
                    'e7'  => '小单',
                    'e8'  => '小双',
                    'e9'  => '极大',
                    'e10' => '极小',
                ];
                break;
            case 'ball_3':
                $arr = [
                    'e1' => '红波',
                    'e2' => '绿波',
                    'e3' => '蓝波',
                ];
                break;
            case 'ball_4':
                $arr = [
                    'e1' => '豹子',
                ];
                break;
            case 'ball_5':
                $arr = [
                    'e1' => '特码三压一',
                ];
                break;
            default:
                break;
        }
        $bet_content = $arr[$column];
        return $bet_content;
    }

    /**
     * 取消注单
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete(Request $request)
    {
        $param = $request->only(['ids'], 'delete');
        $ids = $param['ids'];
        $success = [];
        $fail = [];
        foreach ($ids as $id) {
            $result = $this->cancelOrder($id);
            if ($result['code'] == 1) {
                array_push($success, $id);
            } else {
                array_push($fail, $id);
            }
        }
        $this->jsonData['data']['success'] = $success;
        $this->jsonData['data']['fail'] = $fail;
        $this->jsonData['data']['msg'] = $result;
        return $this->jsonData;
    }

    /**
     * 处理取消注单
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function cancelOrder($id)
    {
        $order = EggOrder::get($id);
        // 判断是否可以取消该订单
        // 0. 已经开奖的不能取消
        if (0 != $order->status) {
            return ['code' => 0, 'msg' => '订单已开奖，不能取消'];
        }
        // 1. 过了封盘时间不能取消
        $eggTime = new EggTimelistController;
        $time = $eggTime->time();
        if ($time['expect'] > $order->expect) {
            return ['code' => 0, 'msg' => '过了封盘时间，不能取消'];
        } else {
            if ($time['endtime'] <= 0) {
                return ['code' => 0, 'msg' => '过了封盘时间，不能取消'];
            }
        }

        // 增加资金变动
        $userMoney   = AccMoney::getMoneyByUserid($this->user->id);
        $user                       = $this->user;
        $chg_column['tokenint']     = $user->tokenint;
        $chg_column['username']     = $user->username;
        $chg_column['nickname']     = $user->nickname;
        $chg_column['c_type']       = CANCEL_ORDER;
        $chg_column['c_old']        = $userMoney->cash_money;
        $chg_column['chg']          = $order['money'];
        $chg_column['cur']          = $chg_column['c_old'] + $chg_column['chg'];
        $chg_column['con']          = 'PC蛋蛋取消注单';
        $chg_column['opr_tokenint'] = '';
        $chg_column['opr_nickname'] = '';
        $chg_column['opr_username'] = '';
        $chg_column['opr_ip']       = request()->ip();
        $chg_column['opr_time']     = get_cur_date();
        $chg_column['opr_mark']     = '订单号：' . $order->order_no;
        $chg_id                     = add_chg($chg_column);
        $chg = AccMychg::get($chg_id);
        // 更新用户余额
        upd_money($chg->tokenint, $chg->cur);

        // 更新订单状态
        $order->status = -1;
        $order->open_stu = 1;
        $order->isUpdate()->save();
        return ['code' => 1, 'msg' => '取消订单成功'];
    }
}
