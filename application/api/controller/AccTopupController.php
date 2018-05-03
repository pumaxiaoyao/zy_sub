<?php

namespace app\api\controller;

use app\api\model\AccTopup;
use app\api\model\AccUsers;
use app\auth\controller\BaseController;
use think\Request;

class AccTopupController extends BaseController
{
    /**
     * 当前用户所有充值记录
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

        $topups = AccTopup::getTopups($page, $per_page, $where);
        $url = request()->baseUrl();
        $data = paginate_data($page, $per_page, $get, $where, $topups, $url, AccTopup::class, 'getTopups');

        $this->jsonData['data'] = $data;

        return $this->jsonData;
    }


    /**
     * 用户充值
     * @param Request $request
     * @return array
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
        $topup = [];
        $user = $this->user;

        $topup['order_no'] = order_no();
        $topup['tokenint'] = $user->tokenint;
        $topup['username'] = $user->username;
        $topup['nickname'] = $user->nickname;
        $topup['money'] = floatval(trim($post['money']));
        $topup['con'] = trim($post['con']);
        $topup['tp_ip'] = $request->ip();
        $topup['tp_time'] = date('Y-m-d H:i:s');
        $topup['tp_stu'] = TOPUP_AUDIT;
        $topup['tp_mark'] = trim($post['tp_mark']);

        $topup_id = AccTopup::addTopup($topup);
        if (is_numeric($topup_id) && $topup_id > 0) {
            $newTopup = AccTopup::getTopupById($topup_id);
            return ['status' => 200, 'msg' => '充值申请提交成功', 'data' => [
                'topup' => $newTopup,
            ]];
        } else {
            return ['status' => 0, 'msg' => '充值申请提交失败，请重试'];
        }
    }

    /**
     * 显示指定的充值记录
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $tokenint = AccTopup::getTokenintById($id);
        if (empty($tokenint)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '该充值记录不存在';
            return $this->jsonData;
        }
        $hasPermission = $this->checkPermission($this->payload, $tokenint);
        if ($hasPermission) {
            $topup = AccTopup::getTopupById($id);
            $this->jsonData['data']['topup'] = $topup;
            return $this->jsonData;
        }
        $this->jsonData['status'] = 403;
        $this->jsonData['msg'] = '您无权查看其他用户的充值记录';
        return $this->jsonData;
    }

}
