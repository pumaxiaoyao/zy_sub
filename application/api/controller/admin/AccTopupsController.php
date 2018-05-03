<?php

namespace app\api\controller\admin;

use app\api\model\AccMychg;
use app\api\model\AccTopup;
use app\api\model\AccMoney;
use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use think\Db;
use think\Request;

class AccTopupsController extends AdminBaseController
{
    /**
     * 用户充值记录列表
     *
     * @param Request $request
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function index (Request $request)
    {
        $params = $request->only(['page', 'per_page', 'username']);

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

        $list = AccTopup::getTopups($page, $per_page);
        $url = $request->baseUrl();
        $data = paginate_data($page, $per_page, $params, $where, $list, $url, AccTopup::class, 'getTopups');

        $this->jsonData['data']['topups'] = $data;

        return $this->jsonData;
    }

    /**
     * 显示指定的充值记录
     *
     * @param $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function read ($id)
    {
        $topup = AccTopup::getTopupById($id);
        if (is_null($topup)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '该充值记录不存在';
            return $this->jsonData;
        } else {
            $this->jsonData['data']['topup'] = $topup;
            return $this->jsonData;
        }
    }


    /**
     * 删除指定充值记录
     *
     * @param $id
     *
     * @return array
     */
    public function delete ($id)
    {
        $res = AccTopup::deleteById($id);
        if ($res) {
            return $this->jsonData;
        } else {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '删除失败，该记录不存在';
            return $this->jsonData;
        }
    }

    /**
     * 审核用户充值申请
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

        $pass = intval($put['pass']);
        $res = AccTopup::auditTopup($id, $pass);
        if (200 === $res) {
            $topup = AccTopup::get($id);

            // 审核通过成功，执行存储过程，添加资金变动记录
            // 更新资金表记录
            if ($pass == 2) {
                $user = AccUsers::getUserByTokenint($topup->tokenint);
                $admin = AccUsers::get($this->payload['user_id']);
                $chg_column['tokenint'] = $user->tokenint;
                $chg_column['username'] = $user->username;
                $chg_column['nickname'] = $user->nickname;
                $chg_column['c_type'] = CHG_TOPUP;
                $chg_column['c_old'] = AccMoney::getCashByUser($user->tokenint);
                $chg_column['chg'] = $topup->money;
                $chg_column['cur'] = $chg_column['c_old'] + $topup->money;
                $chg_column['con'] = '用户充值';
                $chg_column['opr_tokenint'] = $admin->tokenint;;
                $chg_column['opr_nickname'] = $admin->username;;
                $chg_column['opr_username'] = $admin->nickname;;
                $chg_column['opr_ip'] = request()->ip();
                $chg_column['opr_time'] = get_cur_date();
                $chg_column['opr_mark'] = $topup->order_no;

                $chg_id = add_chg($chg_column);
                $chg = AccMychg::get($chg_id);
                // 更新用户余额
                upd_money($chg->tokenint, $chg->cur);
                $topup->chg_id = $chg_id;
                // $topup->tp_mark = '通过充值申请';
            } else {
                // $topup->tp_mark = '驳回充值申请';
            }
            $topup->isUpdate()->save();
            $topup = $topup->toArray();
            unset($topup['tokenint']);
            $this->jsonData['msg'] = '审核操作成功';
            $this->jsonData['data']['topup'] = $topup;
            return $this->jsonData;
        } elseif (404 === $res) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '该充值申请记录不存在';
            return $this->jsonData;
        } elseif (403 === $res) {
            $this->jsonData['status'] = 403;
            $this->jsonData['msg'] = '该充值申请已审核，请匆重复操作';
            return $this->jsonData;
        } else {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '审核操作失败';
            return $this->jsonData;
        }
    }
}
