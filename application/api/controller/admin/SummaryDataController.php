<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/4/3
 * Time: 8:57
 */

namespace app\api\controller\admin;


use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use app\cake\model\CakeOrder;
use app\cqssc\model\SscOrder;
use app\egg\model\EggOrder;
use app\pk10\model\Pk10Order;
use think\Request;

class SummaryDataController extends AdminBaseController
{
    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function index (Request $request)
    {
        $get = $request->only(['lty_type', 'range'], 'get');
        // 参数校验
        $error = $this->validate($get, [
            'lty_type|彩种' => 'alphaNum',
            'range|时间范围' => 'alphaDash',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $range = isset($get['range']) ? trim($get['range']) : 'today';
        $lty_type = isset($get['lty_type']) ? strtolower(trim($get['lty_type'])) : 'all';
        $timeRange = get_time_range($range);

        $where['tokenint'] = ['in', $this->subUserTokens];
        $where['create_time'] = ['between', [$timeRange['start'], $timeRange['end']]];

        $summary = $this->summary($lty_type, $where);
        if (!$summary) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = 'fail';
            return $this->jsonData;
        }

        $this->jsonData['data']['summary'] = $summary;
        return $this->jsonData;
    }

    /**
     * @param $lty_type
     * @param $where
     *
     * @return array|bool
     */
    private function summary ($lty_type, $where)
    {
        switch ($lty_type) {
            case 'all':
                $summaryAll = $this->summaryAll($where);
                return $summaryAll;
                break;
            case 'cqssc':
            case 'ssc':
                $lty_type = 'cqssc';
                $model = SscOrder::class;
                break;
            case 'bjpk10':
            case 'pk10':
                $lty_type = 'bjpk10';
                $model = Pk10Order::class;
                break;
            case 'pcegg':
            case 'egg':
                $lty_type = 'pcegg';
                $model = EggOrder::class;
                break;
            case 'cakeno':
            case 'cake':
                $lty_type = 'cakeno';
                $model = CakeOrder::class;
                break;
            default:
                return false;
        }
        // 未结算订单数量和金额
        $unclearedWhere = $where;
        $unclearedWhere['open_stu'] = 0;
        $unclearedOrders = $model::getOrders($unclearedWhere)->toArray();
        $unclearedCount = count($unclearedOrders);
        $unclearedMoney = array_sum(array_column($unclearedOrders, 'money'));

        // 已结算订单数量和金额
        $clearedWhere = $where;
        $clearedWhere['open_stu'] = 1;
        $clearedOrders = $model::getOrders($clearedWhere)->toArray();
        $clearedCount = count($clearedOrders);
        $clearedMoney = array_sum(array_column($clearedOrders, 'money'));

        // 中奖订单数量和金额
        $luckyWhere = $where;
        $luckyWhere['open_ret'] = 1;
        $luckyOrders = $model::getOrders($luckyWhere)->toArray();
        $luckyCount = count($luckyOrders);
        $luckyMoney = array_sum(array_column($luckyOrders, 'open_win'));

        // 未中奖订单数量和金额
        $unluckyWhere = $where;
        $unluckyWhere['open_ret'] = 0;
        $unluckyWhere['status'] = 1;
        $unluckyOrders = $model::getOrders($unluckyWhere)->toArray();
        $unluckyCount = count($unluckyOrders);
        $unluckyMoney = array_sum(array_column($unluckyOrders, 'open_win'));

        // 累计盈亏
        $yk = -$luckyMoney - $unluckyMoney;

        $return = [
            'unclearedCount' => $unclearedCount,
            'unclearedMoney' => $unclearedMoney,
            'clearedCount' => $clearedCount,
            'clearedMoney' => $clearedMoney,
            'unluckyCount' => $unluckyCount,
            'unluckyMoney' => $unluckyMoney,
            'luckyCount' => $luckyCount,
            'luckyMoney' => $luckyMoney,
            'yk' => $yk,
            'lty_type' => $lty_type
        ];
        return $return;
    }

    /**
     * 全部彩种汇总统计
     *
     * @param $where
     *
     * @return array
     */
    private function summaryAll ($where)
    {
        $sscSummary = $this->summary('cqssc', $where);
        $pk10Summary = $this->summary('bjpk10', $where);
        $eggSummary = $this->summary('pcegg', $where);
        $cakeSummary = $this->summary('cakeno', $where);

        $return['cqssc'] = $sscSummary;
        $return['bjpk10'] = $pk10Summary;
        $return['pcegg'] = $eggSummary;
        $return['cakeno'] = $cakeSummary;

        $summaryList = [];
        array_push($summaryList, $sscSummary);
        array_push($summaryList, $pk10Summary);
        array_push($summaryList, $eggSummary);
        array_push($summaryList, $cakeSummary);

        $unclearedCount = array_sum(array_column($summaryList, 'unclearedCount'));
        $unclearedMoney = array_sum(array_column($summaryList, 'unclearedMoney'));
        $clearedCount = array_sum(array_column($summaryList, 'clearedCount'));
        $clearedMoney = array_sum(array_column($summaryList, 'clearedMoney'));
        $unluckyCount = array_sum(array_column($summaryList, 'unluckyCount'));
        $unluckyMoney = array_sum(array_column($summaryList, 'unluckyMoney'));
        $luckyCount = array_sum(array_column($summaryList, 'luckyCount'));
        $luckyMoney = array_sum(array_column($summaryList, 'luckyMoney'));
        $yk = array_sum(array_column($summaryList, 'yk'));

        $return['all'] = [
            'unclearedCount' => $unclearedCount,
            'unclearedMoney' => $unclearedMoney,
            'clearedCount' => $clearedCount,
            'clearedMoney' => $clearedMoney,
            'unluckyCount' => $unluckyCount,
            'unluckyMoney' => $unluckyMoney,
            'luckyCount' => $luckyCount,
            'luckyMoney' => $luckyMoney,
            'yk' => $yk,
            'lty_type' => 'all',
        ];

        return $return;
    }

    /**
     * 结算报表（本周+上周）
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function clearList(Request $request)
    {
        $get = $request->only('user_id', 'get');
        $user_id = $get['user_id'];
        $tokenint = AccUsers::getTokenint($user_id);
        $weeks = get_weeks();
        $todayDate = date('Y-m-d');
        $todayStart = strtotime($todayDate);
        $weekNum = date('N');
        $lastWeekStart = $todayStart - 86400 * (7 + $weekNum - 1);
        $thisWeekStart = $lastWeekStart + 86400 * 7;

        // 上周
        $lastWeekSum = [];
        for ($i = 0; $i < 7; $i++) {
            $start = $lastWeekStart + 86400 * $i;
            $end = $lastWeekStart + 86400 * ($i + 1);
            $lastWeekSum[$i]['week_name'] = $weeks[$i];
            $lastWeekSum[$i]['date_str'] = date('m-d', $start);
            // 查询报表
            $sum = $this->calcOneDay($start, $end, $tokenint);
            $lastWeekSum[$i]['sum_data'] = $sum;
        }

        // 本周
        $thisWeekSum = [];
        for ($i = 0; $i < 7; $i++) {
            $start = $thisWeekStart + 86400 * $i;
            $end = $thisWeekStart + 86400 * ($i + 1);
            $thisWeekSum[$i]['week_name'] = $weeks[$i];
            $thisWeekSum[$i]['date_str'] = date('m-d', $start);
            // 查询报表
            $sum = $this->calcOneDay($start, $end, $tokenint);
            $thisWeekSum[$i]['sum_data'] = $sum;
        }
        $this->jsonData['data']['last_week'] = $lastWeekSum;
        $this->jsonData['data']['this_week'] = $thisWeekSum;

        return $this->jsonData;
    }

    /**
     * 单天结算报表
     *
     * @param $start
     * @param $end
     *
     * @param $tokenint
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function calcOneDay($start, $end, $tokenint)
    {
        $sscSum = SscOrder::sumData($start, $end, $tokenint)->toArray();
        $pk10Sum = Pk10Order::sumData($start, $end, $tokenint)->toArray();
        $eggSum = EggOrder::sumData($start, $end, $tokenint)->toArray();
        $cakeSum = CakeOrder::sumData($start, $end, $tokenint)->toArray();
        $orderSum = [$sscSum, $pk10Sum, $eggSum, $cakeSum];
        $orderNum = array_sum(array_column($orderSum, 'order_num'));
        $sumMoney = array_sum(array_column($orderSum, 'sum_money'));
        $win = array_sum(array_column($orderSum, 'win'));
        $fs = array_sum(array_column($orderSum, 'fs'));
        $winAndFs = $win + $fs;
        $sum = [
            'order_num' => $orderNum,
            'sum_money' => $sumMoney,
            'win' => $win,
            'fs' => $fs,
            'winAndFs' => $winAndFs,
        ];
        return $sum;
    }

    /**
     * 指定日期下注明细，已做好分页
     * @param Request $request
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function detail(Request $request)
    {
        $get = $request->only(['date', 'page', 'per_page', 'user_id'], 'get');

        // 参数校验
        $error = $this->validate($get, [
            'date|日期' => 'require|alphaDash',
            'page|当前页' => 'number|egt:1',
            'per_page|每页显示数' => 'number|egt:6|elt:20',
            'user_id|用户ID' => 'require|number|gt:1'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }
        $user_id = $get['user_id'];
        $tokenint = AccUsers::getTokenint($user_id);
        $url = $request->baseUrl();
        $orders = $this->oneDayOrders($get, $url, $tokenint);
        $this->jsonData['data'] = $orders;
        return $this->jsonData;
    }

    /**
     *
     * @param $get
     * @param $url
     *
     * @param $tokenint
     *
     * @return array
     * @throws \think\exception\DbException
     */
    private function oneDayOrders($get, $url, $tokenint)
    {
        $date = $get['date'];
        $page = isset($get['page']) ? $get['page'] : 1;
        $per_page = isset($get['per_page']) ? $get['per_page'] : 15;
        $start = strtotime(date('Y') . "-" . $date);
        $end = $start + 86400;

        $where['tokenint'] = [
            '=',
            $tokenint
        ];
        $where['create_time'] = [
            'between' , [
                $start,
                $end
            ]
        ];

        $sscOrders = SscOrder::getOrders($where)->toArray();
        $pk10Orders = Pk10Order::getOrders($where)->toArray();
        $eggOrders = EggOrder::getOrders($where)->toArray();
        $cakeOrders = CakeOrder::getOrders($where)->toArray();
        $orders = array_merge($sscOrders, $pk10Orders, $eggOrders, $cakeOrders);
        usort($orders, 'cmp_time');
        $offset = ($page - 1) * $per_page;
        $retOrders = array_slice($orders, $offset, $per_page);
        $offset2 = $page * $per_page;
        $nextPageData = array_slice($orders, $offset2, $per_page);
        $curPageNum = count($retOrders);
        $nextPageNum = count($nextPageData);
        $sum = count($orders);
        $pageNum = ceil($sum / $per_page);


        $retData = [
            'curPage' => $page,
            'sum' => $sum,
            'pageNum' => $pageNum,
            'curPageNum' => $curPageNum,
            'nextPageNum' => $nextPageNum,
        ];
        //
        if ($page == 1) {
            $retData['hasPrev'] = false;
        } else {
            $prevPage = $page - 1;
            $retData['hasPrev'] = true;
            $get['page'] = $prevPage;
            $retData['prevPageUrl'] = url($url, $get);
        }

        if ($nextPageNum > 0) {
            $retData['hasNext'] = true;
            $nextPage = $page + 1;
            $get['page'] = $nextPage;
            $retData['nextPageUrl'] = url($url, $get);
        } else {
            $retData['hasNext'] = false;
        }
        $retData['orders'] = $retOrders;
        return $retData;
    }

}