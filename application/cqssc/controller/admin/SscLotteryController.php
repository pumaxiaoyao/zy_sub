<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/6
 * Time: 15:58
 */

namespace app\cqssc\controller\admin;


use think\Request;
use app\api\model\AccMoney;
use app\api\model\AccMychg;
use app\api\model\AccUsers;
use app\cqssc\model\SscOrder;
use app\cqssc\model\SscLottery;
use app\cqssc\model\SscTimelist;
use app\cqssc\model\SscLotteryHistory;
use app\auth\controller\AdminBaseController;
use app\cqssc\controller\KaijiangController;

class SscLotteryController extends AdminBaseController
{
    private function getExpects()
    {
        $timelist = SscTimelist::where('draw', '<', date('H:i:s', time()))->select();
        $expects = [];
        foreach ($timelist as &$item) {
            $expect = date('Ymd') . fix_num($item['expect']);
            if (120 == $item['expect']) {
                $expect = date('Ymd', strtotime('-1day')) . fix_num($item['expect']);
            }
            array_push($expects, $expect);
        }
        return $expects;
    }

    /**
     * @param Request $request
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public function history(Request $request)
    {
        $params = $request->param();
        // 页数
        $page = isset($params['page']) ? $params['page'] : 1;
        if ($page < 1) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        // 查询条件
        $where = [];
        if (isset($params['expect'])) {
            $where['expect'] = $params['expect'];
        }
        if (isset($params['is_lottery'])) {
            $where['is_lottery'] = $params['is_lottery'];
        }
        if (isset($params['range'])) {
            $timeRange = get_time_range($params['range']);
            $where['opentimestamp'] = ['between', [$timeRange['start'], $timeRange['end']]];
            if ('today' == $params['range'] || $params['range'] == date('Y-m-d')) {
                $per_page = 120;
            }
        }
        // 开奖历史
        $list = SscLottery::history($page, $per_page, $where);

        // 当前页显示的列表
        $expects = $this->getExpects();
        $kaijiangCtrl = new KaijiangController();
        foreach ($list as $key => &$item) {
            // 解析开奖内容
            $item['open_codes'] = explode(',', $item['opencode']);
            $item['details'] = $this->handleDetails($kaijiangCtrl->getCqsscResult($item['opencode']));
            $res = SscLottery::expectLotteried($item['expect']);
            if ($res) {
                $is_lottery = 1;
            } else {
                $is_lottery = 0;
            }
            $item['is_lottery'] = $is_lottery;            
        }
        if (isset($params['range']) && ('today' == $params['range'] || $params['range'] == date('Y-m-d'))) {
            $listExpects = array_column($list->toArray(), 'expect');
            $diffExpects = array_diff($expects, $listExpects);
            $list = $list->toArray();
            foreach($diffExpects as $diffExp) {
                $item = [];
                $item['expect'] = $diffExp;
                $item['code'] = 'cqssc';
                $item['details'] = '';
                $item['id'] = 0;
                $item['open_codes'] = [];
                $item['opencode'] = '';
                $item['opentime'] = '';
                $item['opentimestamp'] = 0;
                $item['is_lottery'] = 0;
                array_push($list, $item);
            }
            usort($list, 'cmp_expect');
            $this->jsonData['data']['list'] = $list;
        }
        $url = $request->baseUrl();
        $data = paginate_data($page, $per_page, $params, $where, $list, $url, SscLottery::class, 'history');

        $this->jsonData['data'] = $data;
        
        return $this->jsonData;
    }

    /**
     * 加竖线分隔
     * @param $details
     * @return mixed
     */
    private function handleDetails($details)
    {
        foreach ($details as &$detail) {
            $detail = implode(' | ', $detail);
        }
        return $details;
    }

    /**
     * 手动开奖
     *
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function manLottery(Request $request)
    {
        $post = $request->only(['expect', 'open_codes'], 'post');
        $error = $this->validate($post, [
            'expect|期号' => 'require|number',
            'open_codes|开奖号码' => 'require|array|length:5',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }
        // 获取开奖号码
        $expect = trim($post['expect']);
        $open_codes = $post['open_codes'];
        $open_code = implode(',', $open_codes);
        // 检查期数是否存在
        if (!SscLottery::expectExists($expect)) {
            // 漏获取的期数行情手动写入 lottery 表
            $opentime = get_cur_date();
            $lty = new SscLottery();
            $lty->code = 'cqssc';
            $lty->expect = $expect;
            $lty->opencode = $open_code;
            $lty->opentime = $opentime;
            $lty->opentimestamp = strtotime($opentime);
            $lty->is_lottery = 0;
            $lty->save();
        }
        // 检查是否已经开奖的
        if (SscLottery::expectLotteried($expect)) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = "重庆时时彩 [ " . $expect . " ]  已经开奖";
            return $this->jsonData;
        }
        // 开奖结果
        $result = $this->cqsscResult($open_code);
        // 兑奖
        $lastest['expect'] = $expect;
        $lastest['open_code'] = $open_code;
        $msg = $this->setLucky($lastest, $result);
        // 更新开奖状态
        $lottery             = SscLottery::getByExpect($expect);
        $lottery->is_lottery = 1;
        $lottery->save();

        $this->jsonData['msg'] = "重庆时时彩 [ " . $expect . " ]  手动开奖成功";
        return $this->jsonData;
    }

    /**
     * 派奖
     *
     * @param $lastest
     * @param $result
     *
     * @return string
     * @throws \think\exception\DbException
     */
    private function setLucky($lastest, $result)
    {

        $expect = $lastest['expect'];
        $config = config('ssc_open_type');

        // 获取所有本期未开奖注单
        $orders     = SscOrder::getNotOpenOrdersByExpect($expect);
        $orders_num = count($orders);

        if (empty($orders->toArray())) {
            return '[ ' . $expect . ' ] has no orders';
        }

        // 遍历注单
        $upd_orders = [];
        foreach ($orders as $order) {
            $upd_order['id'] = $order->id;
            $type            = array_search($order['mark_a'], $config);
            $bet_contents    = $result[$type];
            if (in_array($order['mark_b'], $bet_contents)) {
                $upd_order['open_ret'] = 1;
                $upd_order['open_win'] = $order['win'] - $order['money'];
                // 派奖
                $this->setBonus($order);
            } else {
                $upd_order['open_ret'] = 0;
                $upd_order['open_win'] = 0 - $order['money'];
            }
            $upd_order['open_stu']    = 1;
            $upd_order['open_code']   = $lastest['opencode'];
            $upd_order['status']      = 1;
            $upd_order['update_time'] = time();
            unset($upd_order['create_time']);
            array_push($upd_orders, $upd_order);
            // 返水，无关是否中奖
            $this->setFs($order);
        }
        $orderModel = new SscOrder();
        $orderModel->isUpdate()->saveAll($upd_orders);

        return "[ " . $expect . " ] $orders_num 注订单";
    }

    /**
     * @param $order
     *
     * @throws \think\exception\DbException
     */
    private function setFs($order)
    {
        $user = AccUsers::getUserByTokenint($order->tokenint);
        $this->addFs($user, $order);
        if ($user->admin) {
            $admin = AccUsers::get($user->admin);
            $this->addFs($admin, $order);
        }
        if ($user->manager) {
            $manager = AccUsers::get($user->manager);
            $this->addFs($manager, $order);
        }
        if ($user->agent) {
            $agent = AccUsers::get($user->agent);
            $this->addFs($agent, $order);
        }
    }

    /**
     * @param $user
     * @param $order
     *
     * @throws \think\exception\DbException
     */
    private function addFs($user, $order)
    {
        $userMoney = AccMoney::getMoneyByUser($user->tokenint);
        if (!$userMoney) {
            AccMoney::initMoney($user);
        }
        $chg = 0;
        switch ($user->type) {
            case 3:
                $chg = $order->fs_gv;
                break;
            case 2:
                $chg = $order->fs_mv;
                break;
            case 1:
                $chg = $order->fs_av;
                break;
            case 0:
                $chg = $order->fs_sv;
                break;
        }
        $chg_column['tokenint']     = $user->tokenint;
        $chg_column['username']     = $user->username;
        $chg_column['nickname']     = $user->nickname;
        $chg_column['c_type']       = CHG_BET_FS;
        $chg_column['c_old']        = AccMoney::getCashByUser($user->tokenint);
        $chg_column['chg']          = $chg;
        $chg_column['cur']          = $chg_column['c_old'] + $chg_column['chg'];
        $chg_column['con']          = '重庆时时彩下注返水';
        $chg_column['opr_tokenint'] = '';
        $chg_column['opr_nickname'] = '';
        $chg_column['opr_username'] = '';
        $chg_column['opr_ip']       = request()->ip();
        $chg_column['opr_time']     = get_cur_date();
        $chg_column['opr_mark']     = $order->order_no;
        $chg_id                     = add_chg($chg_column);

        $chg = AccMychg::get($chg_id);
        // 更新用户余额
        upd_money($chg->tokenint, $chg->cur);
    }

    /**
     * 发放奖金
     *
     * @param $order
     *
     * @throws \think\exception\DbException
     */
    private function setBonus($order)
    {
        $user                       = AccUsers::getUserByTokenint($order->tokenint);
        $chg_column['tokenint']     = $user->tokenint;
        $chg_column['username']     = $user->username;
        $chg_column['nickname']     = $user->nickname;
        $chg_column['c_type']       = CHG_BET_LUCKY;
        $chg_column['c_old']        = AccMoney::getCashByUser($user->tokenint);
        $chg_column['chg']          = $order->win;
        $chg_column['cur']          = $chg_column['c_old'] + $chg_column['chg'];
        $chg_column['con']          = '重庆时时彩中奖';
        $chg_column['opr_tokenint'] = '';
        $chg_column['opr_nickname'] = '';
        $chg_column['opr_username'] = '';
        $chg_column['opr_ip']       = request()->ip();
        $chg_column['opr_time']     = get_cur_date();
        $chg_column['opr_mark']     = '订单号：' . $order->order_no . ', ' . $order->expect . ', ' . $order->mark_a . ', ' . $order->mark_b;
        $chg_id                     = add_chg($chg_column);

        $chg = AccMychg::get($chg_id);
        // 更新用户余额
        upd_money($chg->tokenint, $chg->cur);
    }

    /**
     * 暴露给外部调用
     *
     * @param $open_code
     *
     * @return array
     */
    public function getCqsscResult($open_code)
    {
        return $this->cqsscResult($open_code);
    }

    /**
     * 开奖结果
     *
     * @param $open_code
     *
     * @return array
     */
    private function cqsscResult($open_code)
    {
        $open_codes = explode(',', $open_code);

        $ret = [];
        // 第一球
        $ret['ball_1'] = $this->generateSingleRes($open_codes[0]);

        // 第二球
        $ret['ball_2'] = $this->generateSingleRes($open_codes[1]);

        // 第三球
        $ret['ball_3'] = $this->generateSingleRes($open_codes[2]);

        // 第四球
        $ret['ball_4'] = $this->generateSingleRes($open_codes[3]);

        // 第五球
        $ret['ball_5'] = $this->generateSingleRes($open_codes[4]);

        // 总和、龙虎和
        $ret['dragon_and_tiger'] = [];
        $sum                     = array_sum($open_codes);
        if ($sum < 23) {
            $dragon_and_tiger_res = '总和小';
        } else {
            $dragon_and_tiger_res = '总和大';
        }
        array_push($ret['dragon_and_tiger'], $dragon_and_tiger_res);
        if ($sum % 2 === 0) {
            $half = '总和双';
        } else {
            $half = '总和单';
        }
        array_push($ret['dragon_and_tiger'], $half);
        // 龙虎
        if ($open_codes[0] > $open_codes[4]) {
            $cmp = '龙';
        } elseif ($open_codes[0] < $open_codes[4]) {
            $cmp = '虎';
        } else {
            $cmp = '和';
        }
        array_push($ret['dragon_and_tiger'], $cmp);


        // 前三球
        $front_3        = array_slice($open_codes, 0, 3);
        $front_3_res    = $this->generate3ballsRes($front_3);
        $ret['front_3'] = [$front_3_res];

        // 中三球
        $medium_3        = array_slice($open_codes, 1, 3);
        $medium_3_res    = $this->generate3ballsRes($medium_3);
        $ret['medium_3'] = [$medium_3_res];

        // 后三球
        $end_3        = array_slice($open_codes, 2, 3);
        $end_3_res    = $this->generate3ballsRes($end_3);
        $ret['end_3'] = [$end_3_res];


        return $ret;
    }

    /**
     * 计算特码的大小单双
     *
     * @param $code
     *
     * @return array
     */
    private function generateSingleRes($code)
    {
        $ret = [];
        array_push($ret, $code);

        if ($code % 2 === 0) {
            array_push($ret, '双');
        } else {
            array_push($ret, '单');
        }

        if ($code <= 4) {
            array_push($ret, '小');
        } else {
            array_push($ret, '大');
        }
        return $ret;
    }

    /**
     * 计算三球开奖结果
     *
     * @param $open_codes
     *
     * @return string
     */
    private function generate3ballsRes($open_codes)
    {
        sort($open_codes);
        $first     = $open_codes[0];
        $second    = $open_codes[1];
        $third     = $open_codes[2];
        $straights = [
            '012',
            '123',
            '234',
            '345',
            '456',
            '567',
            '678',
            '789',
            '089',
            '019'
        ];
        $join_str  = implode($open_codes);
        if ($first === $second && $first === $third && $second === $third) {
            $ret = '豹子';
        } elseif (($first === $second && $second !== $third) || ($first === $third && $first !== $second) || ($second === $third && $first !== $third)) {
            $ret = '对子';
        } elseif (in_array($join_str, $straights)) {
            $ret = '顺子';
        } elseif ((abs($first - $second) === 1) || (abs($second - $third) === 1) || (abs($third - $first) === 9)) {
            $ret = '半顺';
        } else {
            $ret = '杂六';
        }

        return $ret;
    }
}