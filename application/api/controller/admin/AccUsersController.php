<?php

namespace app\api\controller\admin;

use app\api\model\AccAuth;
use app\api\model\AccMoney;
use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use app\cake\model\CakeCustomOdds;
use app\cake\model\CakeOdds;
use app\cake\model\CakeRatelist;
use app\cake\model\CakeUser;
use app\cqssc\model\SscCustomOdds;
use app\cqssc\model\SscOdds;
use app\cqssc\model\SscRatelist;
use app\cqssc\model\SscUser;
use app\egg\model\EggCustomOdds;
use app\egg\model\EggOdds;
use app\egg\model\EggRatelist;
use app\egg\model\EggUser;
use app\pk10\model\Pk10CustomOdds;
use app\pk10\model\Pk10Odds;
use app\pk10\model\Pk10Ratelist;
use app\pk10\model\Pk10User;
use think\Request;

class AccUsersController extends AdminBaseController
{
    /**
     * 获取用户列表
     *
     * @param Request $request
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function index (Request $request)
    {
        $params = $request->param();
        $error = $this->validate($params, [
            'page' => 'number|egt:1',
            'per_page' => 'number|egt:5',
            'type' => 'number|between:0,3',
            'manager' => 'number',
            'agent' => 'number',
            'username' => 'alphaDash'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        // 页数
        $page = isset($params['page']) ? $params['page'] : 1;

        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        // 查询条件
        $where['tokenint'] = ['in', $this->subUserTokens];

        generate_conditions($where, $params);

        // 下注列表
        $list = AccUsers::getList($page, $per_page, $where);
        foreach ($list as &$item) {
            $money = AccMoney::getMoneyByUserid($item->user_id);
            $item->money = $money;
            unset($item->tokenint);
        }
        $url = $request->baseUrl();

        $data = paginate_data($page, $per_page, $params, $where, $list, $url, AccUsers::class, 'getList');

        $this->jsonData['data'] = $data;

        return $this->jsonData;
    }


    /**
     * 添加用户
     *
     * @param Request $request
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save (Request $request)
    {
        $postData = $request->only(['username', 'nickname', 'type', 'pwd_1', 'pwd_2', 'lotteries',
            'cash_money', 'credit_money'], 'post');

        $error = $this->validate($postData, [
            'username|用户名' => 'require|min:6|max:20|unique:acc_users|alphaDash',
            'type|用户层级' => 'number|between:0,3',
            'pwd_1|登录密码' => 'require|min:6|max:16|alphaDash',
            'pwd_2|支付密码' => 'require|min:6|max:16|alphaDash',
            'lotteries|彩种选项' => 'require'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        if ('' == trim($postData['nickname'])) {
            $postData['nickname'] = $postData['username'];
        }

        $lotteries = $postData['lotteries'];
        unset($postData['lotteries']);

        $payload = $this->payload;
        $self = $this->user;
        $admin_type = $payload['is_admin'];
        // 自动添加上级
        $postData['admin'] = 1;
        switch ($admin_type) {
            case 3:
                $postData['admin'] = $self->id;
                break;
            case 2:
                $postData['admin'] = $self->admin;
                $postData['manager'] = $payload['user_id'];
                break;
            case 1:
                $postData['admin'] = $self->admin;
                $postData['manager'] = $self->manager;
                $postData['agent'] = $payload['user_id'];
                break;
        }
        if (trim($postData['type']) > $admin_type) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '操作失败！您只能添加下级用户';
            return $this->jsonData;
        }
        $insert_id = AccUsers::addMember($postData);
        if ($insert_id) {
            $user = AccUsers::getMember($insert_id);
            if (is_null($user)) {
                $this->jsonData['status'] = 404;
                $this->jsonData['msg'] = '没有此用户';
                return $this->jsonData;
            }

            $money_id = AccMoney::adminAddMoney($user->id, $postData);
            $money = AccMoney::getMoneyById($money_id);

            // 添加管理成功，添加用户管理相关默认权限
            if (2 == $user->type) {
                $authIds = config('manager_default_user_auth');
                AccAuth::addAuth($insert_id, $authIds);
            }
            if (1 == $user->type) {
                $authIds = config('agent_default_user_auth');
                AccAuth::addAuth($insert_id, $authIds);
            }

            unset($user->tokenint);
            $newLotteries = $this->setOddsList($lotteries);
            $this->jsonData['msg'] = '添加用户成功';
            $this->jsonData['data'] = [
                'user' => $user,
                'money' => $money,
                'lotteries' => $newLotteries,
            ];
            return $this->jsonData;
        } else {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '添加用户失败';
            return $this->jsonData;
        }
    }

    /**
     * 设置彩种
     *
     * @param Request $request
     *
     * @return array
     */
    public function setLottery (Request $request)
    {
        try {
            $postData = $request->only(['user_id', 'lotteries'], 'post');
            $error = $this->validate($postData, [
                'user_id|用户ID' => 'require|number|egt:1',
                'lotteries' => 'require'
            ]);
            if (true !== $error) {
                $this->jsonData['status'] = 0;
                $this->jsonData['msg'] = $error;
                return $this->jsonData;
            }
            $tokenint = AccUsers::getTokenint($postData['user_id']);
            $lotteries = $postData['lotteries'];
            foreach ($lotteries as $lottery => $item) {
                $this->saveLottery($lottery, $item, $tokenint);
            }
            $this->jsonData['msg'] = '操作成功';
            return $this->jsonData;
        } catch (\Exception $e) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '操作失败';
            return $this->jsonData;
        }
    }

    /**
     * @param $lottery
     * @param $item
     * @param $tokenint
     *
     * @throws \Exception
     */
    private function saveLottery ($lottery, $item, $tokenint)
    {
        $user = AccUsers::getUserByTokenint($tokenint);
        $odds_sel = $item['odds_sel'];
        $money = $item['trad'];
        switch ($lottery) {
            case 'cqssc':
                SscRatelist::addPans($odds_sel, $tokenint);
                SscUser::addLtyUser($money, $tokenint);
                if (2 == $user->type) {
                    $authIds = config('manager_default_ssc_auth');
                }
                if (1 == $user->type) {
                    $authIds = config('agent_default_ssc_auth');
                }
                break;
            case 'bjpk10':
                Pk10Ratelist::addPans($odds_sel, $tokenint);
                Pk10User::addLtyUser($money, $tokenint);
                if (2 == $user->type) {
                    $authIds = config('manager_default_pk10_auth');
                }
                if (1 == $user->type) {
                    $authIds = config('agent_default_pk10_auth');
                }
                break;
            case 'pcegg':
                EggRatelist::addPans($odds_sel, $tokenint);
                EggUser::addLtyUser($money, $tokenint);
                if (2 == $user->type) {
                    $authIds = config('manager_default_egg_auth');
                }
                if (1 == $user->type) {
                    $authIds = config('agent_default_egg_auth');
                }
                break;
            case 'cakeno':
                CakeRatelist::addPans($odds_sel, $tokenint);
                CakeUser::addLtyUser($money, $tokenint);
                if (2 == $user->type) {
                    $authIds = config('manager_default_cake_auth');
                }
                if (1 == $user->type) {
                    $authIds = config('agent_default_cake_auth');
                }
                break;
        }
        if (!empty($authIds)) {
            AccAuth::addAuth($user->id, $authIds);
        }
    }

    /**
     * @param $lotteries
     *
     * @return array
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    private function setOddsList ($lotteries)
    {
        $newLottories = [];
        foreach ($lotteries as $lottery) {
            switch ($lottery) {
                case 'cqssc':
                    $names = SscOdds::odds();
                    break;
                case 'bjpk10':
                    $names = Pk10Odds::odds();
                    break;
                case 'pcegg':
                    $names = EggOdds::odds();
                    break;
                case 'cakeno':
                    $names = CakeOdds::odds();
                    break;
                default:
                    $names = [];
                    break;
            }
            $newLottories[$lottery]['odds_list'] = $names;
        }
        return $newLottories;
    }

    /**
     * 查看指定用户
     *
     * @param $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function read ($id)
    {
        $user = AccUsers::get($id);
        $user->money;
        unset_user_fields($user);
        unset($user->money->tokenint);
        $payload = $this->payload;
        $admin_type = $payload['is_admin'];
        switch ($admin_type) {
            case 2:
                $valid = $payload['user_id'] == $user->manager ? true : false;
                break;
            case 1:
                $valid = $payload['user_id'] == $user->agent ? true : false;
                break;
            default:
                $valid = true;
        }
        if (!$valid) {
            $this->jsonData['status'] = 403;
            $this->jsonData['msg'] = '警告！越权操作';
            return $this->jsonData;
        }
        if (is_null($user)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '没有此用户';
            return $this->jsonData;
        } else {
            $uData = $user;
        }

        $this->jsonData['status'] = 200;
        $this->jsonData['data'] = ['user' => $uData];
        return $this->jsonData;
    }

    /**
     * 管理员编辑用户
     *
     * @param $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function edit ($id)
    {
        $user = AccUsers::get($id);
        $user->money;
        unset_user_fields($user);
        unset($user->money->tokenint);
        $payload = $this->payload;
        $admin_type = $payload['is_admin'];
        switch ($admin_type) {
            case 2:
                $valid = $payload['user_id'] == $user->manager ? true : false;
                break;
            case 1:
                $valid = $payload['user_id'] == $user->agent ? true : false;
                break;
            default:
                $valid = true;
        }
        if (!$valid) {
            $this->jsonData['status'] = 403;
            $this->jsonData['msg'] = '警告！越权操作';
            return $this->jsonData;
        }
        if (is_null($user)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '没有此用户';
            return $this->jsonData;
        } else {
            $uData = $user;
        }

        $this->jsonData['status'] = 200;
        $this->jsonData['data'] = ['user' => $uData];
        return $this->jsonData;
    }

    /**
     * 管理员修改用户资料
     *
     * @param Request $request
     * @param         $id
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update (Request $request, $id)
    {
        $put = $request->only(['type', 'nickname', 'cash_money', 'credit_money'], 'put');
        $error = $this->validate($put, [
            'type' => 'number|between:0,3',
            'nickname' => 'chsDash',
            'cash_money' => 'require|number',
            'credit_money' => 'require|number|egt:0',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $user = AccUsers::get($id);
        $payload = $this->payload;
        $admin_type = $payload['is_admin'];
        switch ($admin_type) {
            case 2:
                $valid = $payload['user_id'] == $user->manager ? true : false;
                break;
            case 1:
                $valid = $payload['user_id'] == $user->agent ? true : false;
                break;
            default:
                $valid = true;
        }
        if (!$valid) {
            $this->jsonData['status'] = 403;
            $this->jsonData['msg'] = '警告！越权操作';
            return $this->jsonData;
        }
        if (is_null($user)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '用户不存在';
            return $this->jsonData;
        } else {
            $oldType = $user->type;
            $newType = $put['type'];

            $upd['id'] = $id;
            $upd['type'] = $put['type'];
            $upd['nickname'] = $put['nickname'];
            $res = $user->save($upd);

            if ($oldType != $newType) {
                AccAuth::delAuth($user->tokenint);
                if ($newType != 0) {
                    // 添加管理成功，添加用户管理相关默认权限
                    if (2 == $newType) {
                        $authIds = config('manager_default_user_auth');
                        AccAuth::addAuth($id, $authIds);
                    }
                    if (1 == $newType) {
                        $authIds = config('agent_default_user_auth');
                        AccAuth::addAuth($id, $authIds);
                    }
                    $lotteries = $this->getUserLotteryList($user);
                    foreach ($lotteries as $lottery) {
                        switch ($lottery) {
                            case 'cqssc':
                                if (2 == $newType) {
                                    $authIds = config('manager_default_ssc_auth');
                                }
                                if (1 == $newType) {
                                    $authIds = config('agent_default_ssc_auth');
                                }
                                break;
                            case 'bjpk10':
                                if (2 == $newType) {
                                    $authIds = config('manager_default_pk10_auth');
                                }
                                if (1 == $newType) {
                                    $authIds = config('agent_default_pk10_auth');
                                }
                                break;
                            case 'pcegg':
                                if (2 == $newType) {
                                    $authIds = config('manager_default_egg_auth');
                                }
                                if (1 == $newType) {
                                    $authIds = config('agent_default_egg_auth');
                                }
                                break;
                            case 'cakeno':
                                if (2 == $newType) {
                                    $authIds = config('manager_default_cake_auth');
                                }
                                if (1 == $newType) {
                                    $authIds = config('agent_default_cake_auth');
                                }
                                break;
                        }
                    }
                }
                if (!empty($authIds)) {
                    AccAuth::addAuth($id, $authIds);
                }
            }

            // 修改用户余额和信用额度
            $money = [
                'cash_money' => $put['cash_money'],
                'credit_money' => $put['credit_money'],
            ];
            $result = $this->updateUserMoney($user, $money);
            $newMoney = AccMoney::getMoneyByUser($user->tokenint);
            if ($result || $res) {
                $uData = AccUsers::getMember($id);
                unset($uData->tokenint);

                $this->jsonData['msg'] = '用户信息修改成功';
                $this->jsonData['data']['user'] = $uData;
                $this->jsonData['data']['user']['money'] = $newMoney;
                return $this->jsonData;
            } else {
                $this->jsonData['status'] = 0;
                $this->jsonData['msg'] = '用户信息修改失败';
                return $this->jsonData;
            }
        }
    }

    /**
     * @param $user
     * @param $money
     *
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function updateUserMoney($user, $money)
    {
        $curMoney = AccMoney::getMoneyByUser($user->tokenint);
        // 当前余额
        $old = $curMoney->cash_money;
        // 最新余额
        $new = $money['cash_money'];
        // 余额变动
        $offset = $new - $old;
        $chg = abs($offset);
        if ($offset > 0) {
            $con = "管理员增加余额";
            $type = CHG_TOPUP;
        }
        if ($offset < 0) {
            $con = "管理员减少余额";
            $type = CHG_WITHDRAW;
        }

        // 修改用户余额和信用额度
        $curMoney->cash_money = $money['cash_money'];
        $curMoney->credit_money = $money['credit_money'];
        $result = $curMoney->isUpdate()->save();
        if ($result) {

            // 增加资金变动记录
            if ($offset != 0) {
                $admin = $this->user;
                $chg_column['tokenint']     = $user->tokenint;
                $chg_column['username']     = $user->username;
                $chg_column['nickname']     = $user->nickname;
                $chg_column['c_type']       = $type;
                $chg_column['c_old']        = $old;
                $chg_column['chg']          = $chg;
                $chg_column['cur']          = $new;
                $chg_column['con']          = $con;
                $chg_column['opr_tokenint'] = $admin->tokenint;;
                $chg_column['opr_nickname'] = $admin->username;;
                $chg_column['opr_username'] = $admin->nickname;;
                $chg_column['opr_ip']   = request()->ip();
                $chg_column['opr_time'] = get_cur_date();
                $chg_column['opr_mark'] = $con;
                add_chg($chg_column);
            }
            return true;
        }
        return false;
    }

    /**
     * 删除指定用户(实则禁用)
     *
     * @param $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function delete ($id)
    {
        $user = AccUsers::get($id);
        if (is_null($user)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '用户不存在';
            return $this->jsonData;
        }
        $payload = $this->payload;
        $admin_type = $payload['is_admin'];
        switch ($admin_type) {
            case 2:
                $valid = $payload['user_id'] == $user->manager ? true : false;
                break;
            case 1:
                $valid = $payload['user_id'] == $user->agent ? true : false;
                break;
            default:
                $valid = true;
        }
        if ($user->type == 3) {
            $valid = false;
        }
        if (!$valid) {
            $this->jsonData['status'] = 403;
            $this->jsonData['msg'] = '警告！越权操作';
            return $this->jsonData;
        }

        $result = AccUsers::updateUserStatus($id, BAND);
        if ($result) {
            $this->jsonData['msg'] = '操作成功，用户[' . $user->nickname . ']已被禁用';
            return $this->jsonData;
        } else {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '操作失败';
            return $this->jsonData;
        }
    }

    /**
     * 解禁用户
     *
     * @param $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function unBindUser ($id)
    {
        $user = AccUsers::get($id);
        if (is_null($user)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '用户不存在';
            return $this->jsonData;
        }
        if ($user->status == 1) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '操作失败，用户未被禁用';
            return $this->jsonData;
        }
        $payload = $this->payload;
        $admin_type = $payload['is_admin'];
        switch ($admin_type) {
            case 2:
                $valid = $payload['user_id'] == $user->manager ? true : false;
                break;
            case 1:
                $valid = $payload['user_id'] == $user->agent ? true : false;
                break;
            default:
                $valid = true;
        }
        if (!$valid) {
            $this->jsonData['status'] = 403;
            $this->jsonData['msg'] = '警告！越权操作';
            return $this->jsonData;
        }
        $result = AccUsers::updateUserStatus($id, ALIVE);
        if ($result) {
            $this->jsonData['msg'] = '操作成功，用户[' . $user->nickname . ']已被解禁';
            return $this->jsonData;
        } else {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '操作失败';
            return $this->jsonData;
        }
    }

    /**
     * 查看用户有什么彩种
     *
     * @param $user_id
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function lotteryList ($user_id)
    {
        $user = AccUsers::get($user_id);
        $list = $this->getUserLotteryList($user);

        $this->jsonData['data']['list'] = $list;
        return $this->jsonData;
    }

    /**
     * @param AccUsers $user
     *
     * @return array
     * @throws \think\exception\DbException
     */
    private function getUserLotteryList($user)
    {
        $list = [];
        $sscExists = SscUser::checkExists($user);
        if ($sscExists) {
            array_push($list, 'cqssc');
        }

        $pk10Exists = Pk10User::checkExists($user);
        if ($pk10Exists) {
            array_push($list, 'bjpk10');
        }

        $eggExists = EggUser::checkExists($user);
        if ($eggExists) {
            array_push($list, 'pcegg');
        }

        $cakeExists = CakeUser::checkExists($user);
        if ($cakeExists) {
            array_push($list, 'cakeno');
        }
        return $list;
    }

    /**
     * @param $user_id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function editLottery($user_id)
    {
        $user = AccUsers::get($user_id);
        $allLotteryList = ['cqssc', 'bjpk10', 'pcegg', 'cakeno'];
        $existsLotteryList = $this->getUserLotteryList($user);
        $list = [];
        foreach ($allLotteryList as $key => $item) {
            $list[$key]['name'] = $item;
            $list[$key]['checked'] = 0;
            if (in_array($item, $existsLotteryList)) {
                $list[$key]['checked'] = 1;
            }
        }
        $this->jsonData['data']['list'] = $list;
        return $this->jsonData;
    }

    /**
     * 修改用户彩种，有需要删除的在此方法里删除，添加的需要再配置盘口后再调用 setLottery() 方法
     * @param Request $request
     * @param         $user_id
     *
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updateUserLottery(Request $request, $user_id)
    {
        $post = $request->only(['list'], 'post');

        $error = $this->validate($post, [
            'list|彩种选项' => 'require|length:4'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $user = AccUsers::get($user_id);
        $existLotteryList = $this->getUserLotteryList($user);
        $newLotteryList = $post['list'];
        $addList = [];
        $delList = [];
        foreach ($newLotteryList as $item) {
            if (1 == $item['checked']) {
                array_push($addList, $item['name']);
            } else {
                array_push($delList, $item['name']);
            }
        }

        // 把最新用户要删除的彩种和当前存在的彩种对比，取交集得到待删除彩种
        $toDel = array_intersect($existLotteryList, $delList);
        // 把最新用户要增加的彩种和当前存在的彩种对比，取差集得到待添加彩种
        $toAdd = array_diff($addList, $existLotteryList);

        // 删除彩种
        $this->delLottery($toDel, $user);

        // 增加新彩种
        $newLotteries = $this->setOddsList($toAdd);

        $this->jsonData['data']['lotteries'] = $newLotteries;
        return $this->jsonData;

    }

    /**
     * 删除彩种
     * @param $toDel
     * @param $user
     *
     * @return bool
     */
    private function delLottery($toDel, $user)
    {
        if (empty($toDel)) {
            return true;
        }

        foreach ($toDel as $item) {
            switch ($item) {
                case 'cqssc':
                    SscCustomOdds::delOneByUser($user->tokenint);
                    SscRatelist::delOneByUser($user->tokenint);
                    SscUser::delOneByUser($user->tokenint);
                    break;
                case 'bjpk10':
                    Pk10CustomOdds::delOneByUser($user->tokenint);
                    Pk10Ratelist::delOneByUser($user->tokenint);
                    Pk10User::delOneByUser($user->tokenint);
                    break;
                case 'pcegg':
                    EggCustomOdds::delOneByUser($user->tokenint);
                    EggRatelist::delOneByUser($user->tokenint);
                    EggUser::delOneByUser($user->tokenint);
                    break;
                case 'cakeno':
                    CakeCustomOdds::delOneByUser($user->tokenint);
                    CakeRatelist::delOneByUser($user->tokenint);
                    CakeUser::delOneByUser($user->tokenint);
                    break;
                default:
                    return false;
            }
        }

    }

}
