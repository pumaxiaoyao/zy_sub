<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

use app\cake\model\CakeTradlist;
use app\cqssc\model\SscTradlist;
use app\egg\model\EggTradlist;
use app\pk10\model\Pk10Tradlist;
use GatewayWorker\Lib\Gateway;
use GuzzleHttp\Client;
use think\Db;
use app\api\model\AccUsers;
use think\helper\Time;

// 充值状态常量定义

defined('TOPUP_ACCEPT') or define('TOPUP_ACCEPT', 2);
defined('TOPUP_AUDIT') or define('TOPUP_AUDIT', 1);
defined('TOPUP_REJECT') or define('TOPUP_REJECT', 0);

// 提现状态常量定义
defined('WITHDRAW_ACCEPT') or define('WITHDRAW_ACCEPT', 2);
defined('WITHDRAW_AUDIT') or define('WITHDRAW_AUDIT', 1);
defined('WITHDRAW_REJECT') or define('WITHDRAW_REJECT', 0);

// 资金变动类型常量定义
defined('CHG_TOPUP') or define('CHG_TOPUP', 1);
defined('CHG_WITHDRAW') or define('CHG_WITHDRAW', 2);
defined('CHG_BET') or define('CHG_BET', 3);
defined('CHG_BET_LUCKY') or define('CHG_BET_LUCKY', 4);
defined('CHG_BET_FS') or define('CHG_BET_FS', 5);
defined('CHG_BET_LS') or define('CHG_BET_LS', 6);
defined('CANCEL_ORDER') or define('CANCEL_ORDER', 7);

// 用户登录保持时间长度
defined('KEEP_LOGIN_TIME') or define('KEEP_LOGIN_TIME', 60 * 60 * 24 * 7);


// 用户状态
defined('ALIVE') or define('ALIVE', 1);
defined('BAND') or define('BAND', 0);


/**
 * 生成密钥
 *
 * @param $user_name string 用户名
 * @param $type      string 密钥类型
 *
 * @return string 生成的密钥
 */
function gen_token ($user_name, $type)
{
    return md5(uniqid($type)) . md5(uniqid($user_name));
}

/**
 * 更新用户现金额度
 *
 * @param $tokenint
 * @param $cash_money
 */
function upd_money ($tokenint, $cash_money)
{
    $call_upd_money_procedure = 'call upd_money(:tokenint, :cash_money)';
    $call_upd_money_procedure_params = [
        'tokenint' => $tokenint,
        'cash_money' => $cash_money
    ];
    Db::query($call_upd_money_procedure, $call_upd_money_procedure_params);
}

/**
 * 增加资金变动记录
 *
 * @param $chg_column
 *
 * @return int
 */
function add_chg ($chg_column)
{
    $call_add_chg_procudure = 'call add_chg(:tokenint, :username, :nickname, :c_type, 
                    :c_old, :chg, :cur, :con, :opr_tokenint, :opr_nickname, :opr_username, 
                    :opr_ip, :opr_time, :opr_mark, @last_id)';
    $call_add_chg_procudure_params = [
        'tokenint' => $chg_column['tokenint'],
        'username' => $chg_column['username'],
        'nickname' => $chg_column['nickname'],
        'c_type' => $chg_column['c_type'],
        'c_old' => $chg_column['c_old'],
        'chg' => $chg_column['chg'],
        'cur' => $chg_column['cur'],
        'con' => $chg_column['con'],
        'opr_tokenint' => $chg_column['opr_tokenint'],
        'opr_nickname' => $chg_column['opr_nickname'],
        'opr_username' => $chg_column['opr_username'],
        'opr_ip' => $chg_column['opr_ip'],
        'opr_time' => $chg_column['opr_time'],
        'opr_mark' => $chg_column['opr_mark']
    ];
    $result = Db::query($call_add_chg_procudure, $call_add_chg_procudure_params);

    $chg_id = intval($result[0][0]['last_id']);
    return $chg_id;
}

/**
 * 检查新用户名是否重复
 *
 * @param $username
 *
 * @return bool
 */
function username_is_duplicate ($username)
{
    $user_names = AccUsers::getAllUsername();
    if (in_array($username, $user_names)) {
        return true;
    }
    return false;
}

/**
 * 获取当前日期
 * @return false|string
 */
function get_cur_date ()
{
    return date('Y-m-d H:i:s');
}

/**
 * 从 token 中得到 user_id
 *
 * @param $auth
 *
 * @return mixed
 */
function get_user_id ($auth)
{
    return $auth['data']['payload']['user_id'];
}

/**
 * @param $tokneint
 *
 * @return mixed
 */
function get_user_id_by_tokenint ($tokneint)
{
    return AccUsers::where('tokenint', '=', $tokneint)->value('id');
}


/**
 * 从 token 中得到 tokenint
 *
 * @param $auth
 *
 * @return mixed
 */
function get_user_tokenint ($auth)
{
    $user_id = get_user_id($auth);
    $tokenint = AccUsers::getTokenint($user_id);
    return $tokenint;
}


/**
 * 从 token 中得到 tokenint
 *
 * @param $user_id
 *
 * @return mixed
 */
function get_user_tokenint_by_id ($user_id)
{
    $tokenint = AccUsers::getTokenint($user_id);
    return $tokenint;
}

/**
 * @param $auth
 *
 * @return mixed
 */
function get_payload ($auth)
{
    return $auth['data']['payload'];
}

/**
 * @param $num
 *
 * @return string
 */
function fix_num ($num)
{
    if ($num < 10) {
        $num = '00' . $num;
    }
    if ($num >= 10 && $num < 100) {
        $num = '0' . $num;
    }
    return $num;
}

/**
 * @param float $num
 */
function fix_odds(&$num)
{
    if (is_numeric($num)) {
        $num = round($num, 3);
    }
}

/**
 * @param array $array
 */
function fix_odds_array(&$array)
{
    foreach ($array as &$value) {
        fix_odds($value);
    }
}

/**
 * 产生自定义长度的随机数字符串
 *
 * @param $len
 *
 * @return int
 */
function random_nums ($len)
{
    $chars = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
    $charsLen = count($chars) - 1;
    shuffle($chars);    // 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

/**
 * 产生随机订单号
 * @return string
 */
function order_no ()
{
    $t = explode(' ', microtime());
    return date('Ymd') . $t[1] . ($t[0] * 1 * 1000 * 1000);
}

/**
 * 获取彩种当前各种时间
 *
 * @param $type
 *
 * @return mixed
 */
function get_cur_timelist ($type)
{
    switch ($type) {
        case 'cqssc':
            $timelist = new \app\cqssc\controller\SscTimelistController();
            break;
        case 'pk10':
            $timelist = new \app\pk10\controller\Pk10TimelistController();
            break;
        case 'egg':
            $timelist = new \app\egg\controller\EggTimelistController();
            break;
        case 'cake':
            $timelist = new \app\cake\controller\CakeTimelistController();
            break;
        default:
            $timelist = null;
            break;
    }
    $time = $timelist->time();
    return $time;
}


/**
 * @param $page
 * @param $per_page
 * @param $params
 * @param $where
 * @param $list
 * @param $url
 * @param $model
 *
 * @param $method
 * @return mixed
 */
function paginate_data ($page, $per_page, $params, $where, $list, $url, $model, $method)
{
    $params['per_page'] = $per_page;
    $data['curPage'] = $page;
    // 页码不能小于1
    if ($page == 1) {
        // 如果当前是第一页，则没有前一页
        $data['hasPrev'] = false;
    } else {
        // 当前页码大于1，则有前一页
        $prevPage = $page - 1;
        $data['hasPrev'] = true;
        $params['page'] = $prevPage;
        $data['prevPageUrl'] = url($url, $params);
    }
    // 下页列表
    $nextPageList = $model::$method($page + 1, $per_page, $where);
    $nextPageNum = count($nextPageList);
    // 若下页列表数量大于0，则表示有下一页
    if ($nextPageNum > 0) {
        $nextPage = $page + 1;
        $data['hasNext'] = true;
        $params['page'] = $nextPage;
        $data['nextPageUrl'] = url($url, $params);
    } else {
        // 否则表示没有下一页了
        $data['hasNext'] = false;
    }

    // 总条数
    $sum = $model::where($where)->count();
    $pageNum = ceil($sum / $per_page);

    $data['curPageNum'] = count($list);
    $data['nextPageNum'] = $nextPageNum;
    $data['sum'] = $sum;
    $data['pageNum'] = $pageNum;
    $data['list'] = $list;
    return $data;
}

/**
 * @param $params
 * @param $where
 *
 * @return int|string
 */
function summary_cond ($params, &$where)
{
    if (!isset($params['type'])) {
        $type = 1;
    } else {
        $type = trim($params['type']);
    }
    switch ($type) {
        case 1:
            list($start, $end) = Time::today();
            break;
        case 2:
            list($start, $end) = Time::week();
            break;
        default:
            list($start, $end) = Time::today();
            break;
    }
    $where['create_time'] = [
        ['>=', $start],
        ['<=', $end]
    ];
    return $type;
}

/**
 * 获取头部token
 *
 * @param \think\Request $request
 *
 * @return string
 */
function get_token (\think\Request $request)
{
    return trim(str_ireplace('bearer', '', $request->header('authorization')));
}


/**
 * 添加母盘基础数据后请求其他限额数据并更新
 *
 * @param $trad
 * @param $type
 */
function update_trad (&$trad, $type)
{
    $client = new Client();
    $parts = parse_url($trad->trad_url);
    $api = $parts['scheme'] . '://' . $parts['host'] . '/' . $type . '/tradInfo';
    $response = $client->get($api, [
        'query' => [
            'tokensup' => $trad->trad_tokensup
        ]
    ]);
    $res = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    $trad->trad_cash = $res['data']['acc_money']['cash_money'];
    $trad->trad_credit = $res['data']['acc_money']['credit_money'];
    $trad->trad_min = $res['data']['trad']['money_min'];
    $trad->trad_max = $res['data']['trad']['money_max'];
    $trad->trad_win = $res['data']['trad']['money_win'];
    $trad->save();
}


function get_weeks()
{
    return [
        '星期一',
        '星期二',
        '星期三',
        '星期四',
        '星期五',
        '星期六',
        '星期日',
    ];
}

function cmp_time($a, $b)
{
    if ($a['create_time'] === $b['create_time']) {
        return 0;
    }
    return $a['create_time'] > $b['create_time'] ? -1 : 1;
}

function cmp_expect($a, $b) {
    if ($a['expect'] === $b['expect']) {
        return 0;
    }
    return $a['expect'] > $b['expect'] ? -1 : 1;
}


function lowwer_letters ()
{
    return ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
        'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
}

function odds_table_names ($prefix)
{
    $letters = lowwer_letters();
    $table_names = [];
    foreach ($letters as $letter) {
        $table_name = $prefix . $letter;
        array_push($table_names, $table_name);
    }
    return $table_names;
}

/**
 * @param $table_name
 *
 * @return mixed
 */
function pan_type ($table_name)
{
    $arr = explode('_', trim($table_name));
    return strtoupper($arr[1]);
}


/**
 * 彩种母盘
 *
 * @param $lotteries
 *
 * @return array
 * @throws \think\exception\DbException
 */
function lottery_list ($lotteries)
{
    $ltyList = [];
    foreach ($lotteries as $lottery) {
        $trad_list = get_trad_list($lottery);
        $ltyList[$lottery]['trad_list'] = $trad_list;
    }
    return $ltyList;
}

/**
 * 指定彩种母盘列表
 * @param $lottery
 *
 * @return array|false|static[]
 * @throws \think\exception\DbException
 */
function get_trad_list ($lottery)
{
    switch ($lottery) {
        case 'cqssc':
            $tradlist = SscTradlist::all();
            break;
        case 'bjpk10':
            $tradlist = Pk10Tradlist::all();
            break;
        case 'pcegg':
            $tradlist = EggTradlist::all();
            break;
        case 'cakeno':
            $tradlist = CakeTradlist::all();
            break;
        default:
            $tradlist = [];
            break;
    }
    return $tradlist;
}


/**
 * 通过jwt_token获取用户id
 *
 * @param $token
 *
 * @return mixed
 */
function get_user_id_by_token ($token)
{
    preg_match("#(.*)\.(.*)\.(.*)#", $token, $matches);

    $c = base64_decode($matches[2]);
    $user_id = $c['user_id'];
    return $user_id;
}

/**
 * 获取所有管理员的内部密钥
 *
 * @param  $user
 *
 * @return array
 */
function get_all_admin_client_ids ($user)
{
    $ids = [];
    if (isset($user->manager)) {
        array_push($ids, $user->manager);
    }
    if (isset($user->agent)) {
        array_push($ids, $user->agent);
    }
    $tokenints = AccUsers::where(['type' => 3])->whereOr(['id' => ['in', $ids]])->column('tokenint');
    $client_ids = [];
    foreach ($tokenints as $tokenint) {
        $result = Gateway::getClientIdByUid($tokenint);
        $client_ids = array_merge($client_ids, $result);
    }
    return $client_ids;
}

/**
 * 生成查询条件
 *
 * @param $where
 * @param $params
 */
function generate_conditions (&$where, $params)
{
    if (isset($params['lucky'])) {
        $where['open_ret'] = $params['lucky'];
    }
    if (isset($params['username'])) {
        $where['username'] = ['like', '%' . $params['username'] . '%'];
    }
    if (isset($params['nickname'])) {
        $where['nickname'] = ['like', '%' . $params['nickname'] . '%'];
    }
    if (isset($params['expect'])) {
        $where['expect'] = $params['expect'];
    }
    if (isset($params['status'])) {
        $where['status'] = $params['status'];
    }
    if (isset($params['open_stu'])) {
        $where['open_stu'] = $params['open_stu'];
    }
    if (isset($params['trad_stu'])) {
        $where['trad_stu'] = $params['trad_stu'];
    }
    if (isset($params['type'])) {
        $where['type'] = ['=', $params['type']];
    }
    if (isset($params['manager'])) {
        $where['manager'] = ['=', $params['manager']];
    }
    if (isset($params['agent'])) {
        $where['agent'] = ['=', $params['agent']];
    }
    if (isset($params['status'])) {
        $where['status'] = ['=', $params['status']];
    }
}

/**
 * 删除用户私密字段
 *
 * @param $user
 */
function unset_user_fields (&$user)
{
    unset($user->tokenint);
    unset($user->tokenext);
    unset($user->tokensup);
    unset($user->pwd_1);
    unset($user->pwd_2);
}

function get_route ()
{
    $route = request()->routeInfo();
    return $route['route'];
}

/**
 * 比较两个
 *
 * @param $old_odds
 * @param $new_odds
 *
 * @return array
 */
function compare_odds ($old_odds, $new_odds)
{
    $invalid = [];
    foreach ($old_odds as $i => $old_odd) {
        foreach ($old_odd as $j => $item) {
            if ($item < $new_odds[$i][$j]) {
                array_push($invalid, [$i, $j]);
            }
        }
    }

    return $invalid;
}

function get_time_range ($range = 'today')
{
    // 校验日期时间，以下为指定日期的方式
    $patternDay = '#\d{4}[-|/]\d{1,2}[-|/]\d{1,2}#';
    $patternMonth = '#\d{4}[-|/]\d{1,2}#';
    $patternYear = '#\d{4}#';
    // 校验是否为日期区间
    $dateRange = explode('_', $range);
    if (2 === count($dateRange) && preg_match($patternDay, $dateRange[0]) && preg_match($patternDay, $dateRange[1])) {
        // 指定区间
        $start = strtotime($dateRange[0]);
        $end = strtotime($dateRange[1]) + 86400;
    } elseif (1 === preg_match($patternDay, $range, $match)) {
        // 指定日期
        $date = $match[0];
        $start = strtotime($date);
        $end = $start + 86400;
    } elseif (1 === preg_match($patternMonth, $range, $match)) {
        // 指定月份
        $date = $match[0];
        $start = strtotime($date);
        $end = mktime(23, 59, 59, date('m', $start), date('t', $start), date('Y', $start));
    } elseif (1 === preg_match($patternYear, $range, $match)) {
        // 指定年份
        $date = $match[0];
        $start = mktime(0, 0, 0, 1, 1, $date);
        $end = mktime(23, 59, 59, 12, 31, $date);
    } else {
        $todayDate = date('Y-m-d');
        $todayStart = strtotime($todayDate);
        $weekNum = date('N');
        $lastWeekStart = $todayStart - 86400 * (7 + $weekNum - 1);
        $thisWeekStart = $lastWeekStart + 86400 * 7;
        switch ($range) {
            case "this_week":
                list($start, $end) = [$thisWeekStart, $thisWeekStart + 86400 * 7];
                break;
            case "last_week":
                list($start, $end) = [$lastWeekStart, $lastWeekStart + 86400 * 7];
                break;
            case 'this_month':
                list($start, $end) = Time::month();
                break;
            case 'last_month':
                list($start, $end) = Time::lastMonth();
                break;
            case 'this_year':
                list($start, $end) = Time::year();
                break;
            case 'last_year':
                list($start, $end) = Time::lastYear();
                break;
            default:
                // 默认为当天
                list($start, $end) = Time::today();
                break;
        }
    }
    return [
        'start' => $start,
        'end' => $end,
        'start_date' => date('Y-m-d H:i:s', $start),
        'end_date' => date('Y-m-d H:i:s', $end),
    ];
}

/**
 * 从url中重组域名
 * @param $url
 *
 * @return string
 */
function reset_domain($url) {
    $tmp = parse_url($url);
    $domain = $tmp['scheme'] . "://" . $tmp['host'];
    return $domain;
}



function cmp_num($a, $b)
{
    if ($a['num'] === $b['num']) {
        return 0;
    }
    return $a['num'] > $b['num'] ? -1 : 1;
}


/**
 * 比较下注金额
 * @param $class
 * @param $odds
 * @param $bet
 * @param $order
 * @param $odds_id
 * @param $mark_b
 * @param $expect_orders
 * @return bool
 */
function compare_bet_money($class, $odds, $bet, $order, $odds_id, $mark_b, &$expect_orders)
{
    // 判断玩法限额，包含单注和单期限额
    $bet_limit = $odds[$odds_id - 1]['bet_limit'];
    // 比较单注
    $order_limit_min = $bet_limit['order_limit_min'];
    $order_limit_max = $bet_limit['order_limit_max'];
    if ($bet['money'] < $order_limit_min) {
        $jsonData['status'] = 0;
        $jsonData['msg'] = "[ " . $order['mark_a'] . " ]下注金额不足单注最低限额";
        return $jsonData;
    }
    if ($bet['money'] > $order_limit_max) {
        $jsonData['status'] = 0;
        $jsonData['msg'] = "[ " . $order['mark_a'] . " ]下注金额超过单注最高限额";
        return $jsonData;
    }

    // 比较单期，包括已经下注和即将下注的
    $expect_limit = $bet_limit['expect_limit'];
    if (empty($expect_orders) || empty($expect_orders[$odds_id])) {
        $sum = 0;
    } else {
        $sum = $expect_orders[$odds_id]['sum_money'];
    }
    $expect_orders[$odds_id]['sum_money'] = $sum + $bet['money'];
    $betedSumMoney = $class::getSumMoneyByExpectAndMarkA($bet['expect'], $mark_b);
    if ($betedSumMoney + $expect_orders[$odds_id]['sum_money'] > $expect_limit) {
        $jsonData['status'] = 0;
        $jsonData['msg'] = $bet['expect'] . "期 [ " . $order['mark_a'] . " ]单期下注金额超过单期最高限额";
        return $jsonData;
    }
    return true;
}