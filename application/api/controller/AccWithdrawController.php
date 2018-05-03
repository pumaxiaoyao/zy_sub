<?php

namespace app\api\controller;

use app\api\model\AccMdraw;
use app\api\model\AccMoney;
use app\api\model\AccMychg;
use app\api\model\AccUsers;
use app\auth\controller\BaseController;
use think\Db;
use think\Request;

class AccWithdrawController extends BaseController
{
    /**
     * 当前用户所有提现记录
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $get = \request()->only([
            'page',
            'per_page'
        ], 'get');

        $error = $this->validate($get, [
            'page|页码'             => 'number|egt:1',
            'per_page|每页显示'     => 'number|egt:5',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }


        // 页数
        $page = isset($get['page']) ? $get['page'] : 1;
        if ($page < 1) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($get['per_page']) ? $get['per_page'] : 15;

        $where['tokenint'] = $this->user->tokenint;

        $withdraws = AccMdraw::getWithdraws($page, $per_page, $where);
        $url = request()->baseUrl();
        $data = paginate_data($page, $per_page, $get, $where, $withdraws, $url, AccMdraw::class, 'getWithdraws');

        $this->jsonData['data'] = $data;

        return $this->jsonData;
    }


    /**
     * 用户提现申请
     * @param Request $request
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $post = $request->only(['money', 'con', 'tp_mark'], 'post');
        $error = $this->validate($post, [
            'money' => 'require|number|gt:0'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }
        $withdraw = [];
        $withdraw_money = floatval(trim($post['money']));
        $user = $this->user;

        // 检查用户余额是否足够提现，信用额度不能作为提现额度
        $enough = $this->checkCash($user->tokenint, $withdraw_money);
        if (!$enough) {
            return ['status' => 0, 'msg' => '对不起，您的余额不足，无法申请提现'];
        }

        $withdraw['order_no'] = order_no();
        $withdraw['tokenint'] = $user->tokenint;
        $withdraw['username'] = $user->username;
        $withdraw['nickname'] = $user->nickname;
        $withdraw['money'] = $withdraw_money;
        $withdraw['con'] = trim($post['con']);
        $withdraw['tp_ip'] = $request->ip();
        $withdraw['tp_time'] = date('Y-m-d H:i:s');
        $withdraw['tp_stu'] = TOPUP_AUDIT;
        $withdraw['tp_mark'] = trim($post['tp_mark']);

        $withdraw_id = AccMdraw::addWithdraw($withdraw);
        // 提现申请成功
        if (is_numeric($withdraw_id) && $withdraw_id > 0) {
            $newWithdraw = AccMdraw::get($withdraw_id);

            // 调用存储过程，将提现写入资金变动
            $user = AccUsers::getUserByTokenint($newWithdraw->tokenint);
            $chg_column['tokenint'] = $user->tokenint;
            $chg_column['username'] = $user->username;
            $chg_column['nickname'] = $user->nickname;
            $chg_column['c_type'] = CHG_WITHDRAW;
            $chg_column['c_old'] = AccMoney::getCashByUser($user->tokenint);
            $chg_column['chg'] = $newWithdraw->money;
            $chg_column['cur'] = $chg_column['c_old'] - $newWithdraw->money;
            $chg_column['con'] = '用户提现';
            $chg_column['opr_tokenint'] = '系统';
            $chg_column['opr_nickname'] = '系统';
            $chg_column['opr_username'] = '系统';
            $chg_column['opr_ip'] = request()->ip();
            $chg_column['opr_time'] = get_cur_date();
            $chg_column['opr_mark'] = $withdraw['order_no'];
            $chg_id = add_chg($chg_column);

            $chg = AccMychg::get($chg_id);
            // 更新用户余额
            upd_money($chg->tokenint, $chg->cur);

            $newWithdraw->chg_id = $chg_id;
//            $newWithdraw->tp_mark = trim($post['tp_mark']);
            $newWithdraw->isUpdate()->save();

            return ['status' => 200, 'msg' => '提现申请提交成功', 'data' => [
                'withdraw' => $newWithdraw,
            ]];
        } else {
            return ['status' => 0, 'msg' => '提现申请提交失败，请重试'];
        }
    }

    /**
     * 检查用户现金额度是否足够提现
     * @param $tokenint
     * @param $withdraw_money
     * @return bool
     */
    protected function checkCash($tokenint, $withdraw_money)
    {
        $cash_money = AccMoney::getCashByUser($tokenint);
        if ($cash_money > $withdraw_money) {
            return true;
        }
        return false;
    }

    /**
     * 显示指定的提现记录
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $tokenint = AccMdraw::getTokenintById($id);
        if (empty($tokenint)) {
            return ['status' => 404, 'msg' => '该提现记录不存在', 'data' => [
                'withdraw' => []
            ]];
        }
        $hasPermission = $this->checkPermission($this->payload, $tokenint);
        if ($hasPermission) {
            $withdraw = AccMdraw::getWithdrawById($id);
            return ['status' => 200, 'msg' => 'success', 'data' => [
                'withdraw' => $withdraw
            ]];
        }
        return ['status' => 403, 'msg' => '您无权查看其他用户的提现记录', 'data' => [
            'withdraw' => []
        ]];
    }
}
