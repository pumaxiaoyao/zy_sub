<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/7
 * Time: 16:02
 */

namespace app\egg\controller;


use app\api\model\AccMoney;
use app\api\model\AccMychg;
use app\api\model\AccUsers;
use app\egg\model\EggLottery;
use app\egg\model\EggOrder;
use think\Config;
use app\auth\controller\BaseController;

class KaijiangController extends BaseController
{
    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Config::load(APP_PATH . 'egg/config.php');
    }

    /**
     * 最新开奖
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function lastLty()
    {
        $lastLottery = EggLottery::getLastest()->toArray();
        $details = $this->getEggResult($lastLottery['opencode']);
        $lastLottery['opencode'] = explode(',', $lastLottery['opencode']);
        $lastLottery['details'] = $details;
        $lastLottery['code'] = 'egg';

        $get = request()->only('range', 'get');
        $error = $this->validate($get, [
            'range' => 'alphaDash'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }
        $range = isset($get['range']) ? $get['range'] : 'all';
        $where = [];
        $where['tokenint'] = $this->user->tokenint;
        if ('all' !== $range) {
            $timeRange = get_time_range($range);
            $where['create_time'] = ['between', [$timeRange['start'], $timeRange['end']]];
        }
        $unclearMoney = EggOrder::getUnclearMoney($where);
        $lastLottery['unclear_money'] = $unclearMoney;

        return $lastLottery;
    }

    /**
     * 开奖
     * @return string
     * @throws \think\exception\DbException
     */
    public function kjEgg()
    {
        echo "pcegg\tstart\tkaijiang...\n";
        $start = time();
        $lastest = EggLottery::getLastest();
        $expect = $lastest['expect'];
        $open_code = $lastest['opencode'];
        // 检查是否已经开奖的
        if (EggLottery::expectLotteried($expect)) {
            echo "\t[ " . $expect . " ]  \tAlready Lottery \n";
        }

        // 开奖结果
        $result = $this->eggResult($open_code);
        // 兑奖
        $msg = $this->setLucky($lastest, $result);
        // 更新开奖状态
        $lottery = EggLottery::getByExpect($expect);
        $lottery->is_lottery = 1;
        $lottery->save();
        $end = time();
        $offset = $end - $start;
        echo "pcegg\t" . $msg . "\t" . "Lottery Successful\t[Time: {$offset} s]\n\n";
    }

    /**
     * 开奖
     * @return string
     * @throws \think\exception\DbException
     */
    public function lottery()
    {
        $lastest = EggLottery::getLastest();
        $expect = $lastest['expect'];
        $open_code = $lastest['opencode'];
        // 检查是否已经开奖的
        if (EggLottery::expectLotteried($expect)) {
            return "\t[ " . $expect . " ]  \tAlready Lottery ";
        }

        // 开奖结果
        $result = $this->eggResult($open_code);
        // 兑奖
        $msg = $this->setLucky($lastest, $result);
        // 更新开奖状态
        $lottery = EggLottery::getByExpect($expect);
        $lottery->is_lottery = 1;
        $lottery->save();

        return $msg . "\t" . 'Lottery Successful';
    }

    /**
     * 开奖
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function manualLottery()
    {
        $start = time();
        $lastest = EggLottery::getLastest();
        $expect = $lastest['expect'];
        $open_code = $lastest['opencode'];
        // 检查是否已经开奖的
        if (EggLottery::expectLotteried($expect)) {
            return [
                'code' => 0,
                'msg' => "PC蛋蛋 [ " . $expect . " ]  已经开奖"
            ];
        }

        // 开奖结果
        $result = $this->eggResult($open_code);
        // 兑奖
        $msg = $this->setLucky($lastest, $result);
        // 更新开奖状态
        $lottery = EggLottery::getByExpect($expect);
        $lottery->is_lottery = 1;
        $lottery->save();

        $end = time();
        $offset = $end - $start;

        return [
            'code' => 1,
            'msg' => "PC蛋蛋 " . $msg . " 开奖成功 [Time: " . $offset . " s]"
        ];
    }

    /**
     * 派奖
     *
     * @param $latest
     * @param $result
     *
     * @return string
     * @throws \think\exception\DbException
     */
    private function setLucky($latest, $result)
    {

        $expect = $latest['expect'];
        $config = config('egg_open_type');

        // 获取所有本期未开奖注单
        $orders = EggOrder::getNotOpenOrdersByExpect($expect);
        $orders_num = count($orders);

        if (empty($orders->toArray())) {
            return '[ ' . $expect . ' ] has no orders';
        }

        // 遍历注单
        $upd_orders = [];
        foreach ($orders as $order) {
            $upd_order['id'] = $order->id;
            $type = array_search($order['mark_a'], $config);
            $bet_contents = $result[$type];
            if (in_array($order['mark_b'], $bet_contents)) {
                $upd_order['open_ret'] = 1;
                $upd_order['open_win'] = $order['win'] - $order['money'];
                $this->setBonus($order);
            } else {
                $upd_order['open_ret'] = 0;
                $upd_order['open_win'] = 0 - $order['money'];
            }
            $upd_order['open_code'] = implode(',', $result['ball_0']);
            $upd_order['status'] = 1;
            $upd_order['open_stu'] = 1;
            $upd_order['update_time'] = time();
            unset($upd_order['create_time']);
            array_push($upd_orders, $upd_order);
            // 返水，无关是否中奖
            $this->setFs($order);
        }
        $orderModel = new EggOrder();
        $orderModel->isUpdate()->saveAll($upd_orders);

        return "\t[ " . $expect . " ] $orders_num 注订单";
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
        $chg_column['tokenint'] = $user->tokenint;
        $chg_column['username'] = $user->username;
        $chg_column['nickname'] = $user->nickname;
        $chg_column['c_type'] = CHG_BET_FS;
        $chg_column['c_old'] = AccMoney::getCashByUser($user->tokenint);
        $chg_column['chg'] = $chg;
        $chg_column['cur'] = $chg_column['c_old'] + $chg_column['chg'];
        $chg_column['con'] = 'PC蛋蛋下注返水';
        $chg_column['opr_tokenint'] = '';
        $chg_column['opr_nickname'] = '';
        $chg_column['opr_username'] = '';
        $chg_column['opr_ip'] = request()->ip();
        $chg_column['opr_time'] = get_cur_date();
        $chg_column['opr_mark'] = $order->order_no;
        $chg_id = add_chg($chg_column);

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
        $user = AccUsers::getUserByTokenint($order->tokenint);
        $chg_column['tokenint'] = $user->tokenint;
        $chg_column['username'] = $user->username;
        $chg_column['nickname'] = $user->nickname;
        $chg_column['c_type'] = CHG_BET_LUCKY;
        $chg_column['c_old'] = AccMoney::getCashByUser($user->tokenint);
        $chg_column['chg'] = $order->win;
        $chg_column['cur'] = $chg_column['c_old'] + $chg_column['chg'];
        $chg_column['con'] = 'PC蛋蛋中奖';
        $chg_column['opr_tokenint'] = '';
        $chg_column['opr_nickname'] = '';
        $chg_column['opr_username'] = '';
        $chg_column['opr_ip'] = request()->ip();
        $chg_column['opr_time'] = get_cur_date();
        $chg_column['opr_mark'] = '订单号：' . $order->order_no;
        $chg_id = add_chg($chg_column);

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
    public function getEggResult($open_code)
    {
        return $this->eggResult($open_code);
    }

    /**
     * 开奖结果
     *
     * @param $open_code
     *
     * @return array
     */
    private function eggResult($open_code)
    {
        $open_codes = explode(',', $open_code);
        $ret = [];
        // 三位开奖号码
        if (count($open_codes) === 3) {
            $firstNum = $open_codes[0];
            $secondNum = $open_codes[1];
            $thirdNum = $open_codes[2];
        } else {
            $firstNum = $this->generateRes($open_codes, 0, 6);
            $secondNum = $this->generateRes($open_codes, 6, 6);
            $thirdNum = $this->generateRes($open_codes, 12, 6);
        }

        $ret['ball_0'] = [
            $firstNum,
            $secondNum,
            $thirdNum
        ];

        $sum = $firstNum + $secondNum + $thirdNum;
        // 特码
        $ret['ball_1'] = [$sum];

        // 混合玩法
        $ret['ball_2'] = $this->generateSum($sum);

        // 波色
        $ret['ball_3'] = [$this->generateColor($sum)];

        // 豹子
        $ret['ball_4'] = ['非豹子'];
        if ($firstNum === $secondNum && $secondNum === $thirdNum) {
            $ret['ball_4'] = ['豹子'];
        }
        // 特码三压一
        $ret['ball_5'] = [];

        return $ret;
    }

    /**
     * 计算PC蛋蛋波色
     *
     * @param $sum
     *
     * @return string
     */
    private function generateColor($sum)
    {
        if ($sum !== 0 && $sum !== 13 && $sum !== 14 && $sum !== 27) {
            if ($sum % 3 === 0) {
                $color = '红波';
            } elseif (($sum - 1) % 3) {
                $color = '蓝波';
            } else {
                $color = '绿波';
            }
        } else {
            $color = '白';
        }
        return $color;
    }

    /**
     * 计算和值的大小单双
     *
     * @param $sum
     *
     * @return array
     */
    private function generateSum($sum)
    {
        $ret = [];
        // 大小
        if ($sum <= 13) {
            $sb = '小';
        } else {
            $sb = '大';
        }
        $ret[0] = $sb;

        // 单双
        if ($sum % 2 === 0) {
            $sd = '双';
        } else {
            $sd = '单';
        }
        $ret[1] = $sd;

        // 大小单双
        $ret[2] = $sb . $sd;

        // 极值
        $extream = '非极值';
        if ($sum <= 5) {
            $extream = '极小';
        }
        if ($sum >= 22) {
            $extream = '极大';
        }
        $ret[3] = $extream;

        return $ret;
    }

    /**
     * 计算指定范围号码的和值末位数
     *
     * @param $open_codes
     * @param $start
     * @param $length
     *
     * @return int
     */
    private function generateRes($open_codes, $start, $length)
    {
        $new_codes = array_slice($open_codes, $start, $length);
        $sum = array_sum($new_codes);
        $num = $sum % 10;
        return $num;
    }

    /**
     * 长龙统计
     *
     * @return void
     */
    public function longDragon()
    {
        $return = [];
        $lotteries = EggLottery::getToday();
        $half_big_small = [];
        $half_even_odd = [];
        $color = [];
        foreach ($lotteries as $key => $lottery) {
            $result = $this->eggResult($lottery->opencode);
            // 两面
            array_push($half_big_small, $result['ball_2'][0]);
            array_push($half_even_odd, $result['ball_2'][1]);
            // 颜色
            array_push($color, $result['ball_3'][0]);
        }
        $oddAndEven = $this->calcOddAndEvenMost($half_even_odd);
        $bigAndSmall = $this->calcBigAndSmallMost($half_big_small);
        $color = $this->calcColorMost($color);
        $return = array_merge($oddAndEven, $bigAndSmall, $color);
        $newReturn = [];
        foreach ($return as $k => $item) {
            if (!empty($item)) {
                array_push($newReturn, $item);
            }
        }
        usort($newReturn, 'cmp_num');
        $this->jsonData['data'] = $newReturn;
        return $this->jsonData;
    }

    /**
     * 颜色玩法长龙统计
     *
     * @param array $array
     * @return array
     */
    private function calcColorMost($array)
    {
        $calc_red = [];
        $calc_blue = [];
        $calc_green = [];
        $count_red = 0;
        $count_blue = 0;
        $count_green = 0;
        foreach ($array as $item) {
            if ($item === "红波") {
                $count_blue++;
                $count_green++;
                $calc_red[$count_red][] = $item;
            }
            if ($item === "蓝波") {
                $count_red++;
                $count_green++;
                $calc_blue[$count_blue][] = $item;
            }
            if ($item === "绿波") {
                $count_blue++;
                $count_red++;
                $calc_green[$count_green][] = $item;
            }
            if ($item === "白") {
                $count_red++;
                $count_blue++;
                $count_green++;
            }
        }
        $calc_red = $this->genReturn($calc_red, "红波");
        $calc_blue = $this->genReturn($calc_blue, "蓝波");
        $calc_green = $this->genReturn($calc_green, "绿波");
        return [$calc_red, $calc_blue, $calc_green];
    }

    /**
     * 奇偶长龙统计
     *
     * @param array $array
     * @return array
     */
    private function calcOddAndEvenMost($array)
    {
        $calc_odd = []; // jishu
        $calc_even = []; // oushu
        $count_odd = 0;
        $count_even = 0;
        foreach ($array as $item) {
            if ($item === "单") {
                $count_even++;
                $calc_odd[$count_odd][] = $item;
            } else {
                $count_odd++;
                $calc_even[$count_even][] = $item;
            }
        }
        $calc_odd = $this->genReturn($calc_odd, "单");
        $calc_even = $this->genReturn($calc_even, "双");
        return [$calc_odd, $calc_even];
    }

    /**
     * 大小长龙统计
     *
     * @param array $array
     * @return array
     */
    private function calcBigAndSmallMost($array)
    {
        $calc_big = []; // jishu
        $calc_small = []; // oushu
        $count_big = 0;
        $count_small = 0;
        foreach ($array as $item) {
            if ($item === "大") {
                $count_small++;
                $calc_big[$count_big][] = $item;
            } else {
                $count_big++;
                $calc_small[$count_small][] = $item;
            }
        }
        $calc_big = $this->genReturn($calc_big, "大");
        $calc_small = $this->genReturn($calc_small, "小");
        return [$calc_big, $calc_small];
    }

    /**
     * 统计最大出现期数，小于连续3期不出的不统计
     *
     * @param array $array
     * @param string $name
     * @return array
     */
    private function genReturn($array, $name)
    {
        $prefix = '混合-';
        if (in_array($name, ['红波', '蓝波', '绿波'])) {
            $prefix = '';
        }
        $newArray = [];
        foreach ($array as $item) {
            $num = count($item);
            $newItem['name'] = $prefix . $name;
            $newItem['num'] = $num;
            if ($num >= 3) {
                array_push($newArray, $newItem);
            }
        }

        if (empty($newArray) || is_null($newArray)) {
            $return = [];
        } else {
            usort($newArray, 'cmp_num');
            $return = $newArray[0];
        }
        return $return;
    }
}