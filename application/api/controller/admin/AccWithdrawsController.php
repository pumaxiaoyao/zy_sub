<?php

namespace app\api\controller\admin;

use app\api\model\AccMdraw;
use app\api\model\AccMoney;
use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use think\Db;
use think\Request;

class AccWithdrawsController extends AdminBaseController
{
    /**
     * 用户提现记录列表
     *
     * @param Request $request
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function index (Request $request)
    {
        $params = $request->only('page, per_page, username');

        $error = $this->validate($params, [
            'page' => 'number|egt:1',
            'per_page' => 'number|egt:5',
            'username' => 'alphaDash'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        // 查询条件
        $where['tokenint'] = ['in', $this->subUserTokens];
        generate_conditions($where, $params);
        $list = AccMdraw::getWithdraws($page, $per_page, $where);
        $url = $request->baseUrl();
        $data = paginate_data($page, $per_page, $params, $where, $list, $url, AccMdraw::class, 'getWithdraws');

        $this->jsonData['data']['withdraws'] = $data;

        return $this->jsonData;
    }

    /**
     * 显示指定的提现记录
     *
     * @param $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function read ($id)
    {
        $withdraw = AccMdraw::getWithdrawById($id);
        if (is_null($withdraw)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '该提现记录不存在';
            return $this->jsonData;
        } else {
            $this->jsonData['data']['withdraw'] = $withdraw;
            return $this->jsonData;
        }
    }


    /**
     * 删除指定提现记录
     *
     * @param $id
     *
     * @return array
     */
    public function delete ($id)
    {
        $res = AccMdraw::deleteById($id);
        if ($res) {
            return $this->jsonData;
        } else {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '删除失败，该记录不存在';
            return $this->jsonData;
        }
    }

    /**
     * 审核用户提现申请
     *
     * @param Request $request
     * @param         $id
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function update (Request $request, $id)
    {
        $put = $request->only('pass', 'put');

        $error = $this->validate($put, [
            'pass' => 'require|number|in:0,2',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

//        $pass = $put['pass'];
        $res = AccMdraw::auditWithdraw($id, $put['pass']);
        if (201 === $res) {
            $withdraw = AccMdraw::get($id);

            // 审核通过成功，执行存储过程，修改资金变动记录
//            $tp_mark = $pass == 2 ? '通过提现申请' : '驳回提现申请';
            $this->updateChg($withdraw, $this->payload['user_id']);
//            $withdraw->tp_mark = $tp_mark;
//            $withdraw->isUpdate()->save();

            $this->jsonData['msg'] = '审核操作成功';
            $this->jsonData['data']['withdraw'] = $withdraw;
            return $this->jsonData;
        } elseif (404 === $res) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '该提现申请记录不存在';
            return $this->jsonData;
        } elseif (403 === $res) {
            $this->jsonData['status'] = 403;
            $this->jsonData['msg'] = '该提现申请已审核，请匆重复操作';
            return $this->jsonData;
        } else {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '审核操作失败';
            return $this->jsonData;
        }
    }

    /**
     * 存储过程：提现审核完成后更新资金变动记录
     *
     * @param $withdraw
     * @param $user_id
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    private function updateChg ($withdraw, $user_id)
    {
        $admin = AccUsers::get($user_id);
        $cash = AccMoney::getCashByUser($withdraw->tokenint);
        if (2 == $withdraw->tp_stu) {
            $opr_mark = '通过用户提现申请';
            $con = '用户提现成功';
        } else {
            $cash = $cash + $withdraw->money;
            $opr_mark = '拒绝用户提现申请';
            $con = '用户提现失败';
        }

        // 提现审核通过，同时要修改资金表中的记录，拒绝则无操作
        $call_upd_chg_procudure = 'call upd_chg(:id, :chg, :cur, :con, :opr_tokenint,
            :opr_nickname, :opr_username, :opr_ip, :opr_time, :opr_mark)';
        $call_upd_chg_procudure_params = [
            'id' => $withdraw->chg_id,
            'chg' => $withdraw->money,
            'cur' => $cash,
            'con' => $con,
            'opr_tokenint' => $admin->tokenint,
            'opr_nickname' => $admin->nickname,
            'opr_username' => $admin->username,
            'opr_ip' => $this->request->ip(),
            'opr_time' => date('Y-m-d H:i:s'),
            'opr_mark' => $opr_mark
        ];
        $result = Db::query($call_upd_chg_procudure, $call_upd_chg_procudure_params);

        // 更新用户现金额度
        upd_money($withdraw->tokenint, $cash);

        return $result;
    }
}
