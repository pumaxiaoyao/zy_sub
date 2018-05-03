<?php

namespace app\api\controller;

use app\api\model\AccBanks;
use app\api\model\AccUsers;
use app\auth\controller\BaseController;
use think\Request;

class AccBanksController extends BaseController
{
    /**
     * 查看自己所有绑定账号
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $tokenint = AccUsers::getTokenint($this->payload['user_id']);
        $banks = AccBanks::banks($tokenint);
        $this->jsonData['data']['banks'] = $banks;
        return $this->jsonData;
    }


    /**
     * 添加一个绑定提现账号
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $post = $request->only(['bankname', 'realname', 'bknumber', 'address'], 'post');

        $error = $this->validate($post, [
            'bankname|银行名称'     => 'require|chsDash',
            'realname|真实姓名'     => 'require|chs',
            'bknumber|账号'         => 'require|alphaDash',
            'address|地址'          => 'require|chsDash'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $user = AccUsers::get($this->payload['user_id']);
        $res = AccBanks::addBank($post, $user);
        if ($res) {
            $this->jsonData['msg'] = '添加成功';
            return $this->jsonData;
        } else {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '添加失败';
            return $this->jsonData;
        }
    }

    /**
     * 查看绑定提现账号
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $tokenint = AccBanks::getTokenintById($id);
        if (is_null($tokenint)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '该提现账户不存在';
            return $this->jsonData;
        }
        $hasPermission = $this->checkPermission($this->payload, $tokenint);
        if ($hasPermission) {
            $bank = AccBanks::bank($id);
            $this->jsonData['data']['bank'] = $bank;
            return $this->jsonData;
        } else {
            $this->jsonData['status'] = 403;
            $this->jsonData['msg'] = '您无权查看其他用户的信息';
            return $this->jsonData;
        }
    }

    /**
     * 修改指定绑定账号信息
     * @param Request $request
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update(Request $request, $id)
    {
        $put = $request->only(['bankname', 'realname', 'bknumber', 'address'], 'put');

        $error = $this->validate($put, [
            'bankname|银行名称'      => 'chsDash',
            'realname|真实姓名'  => 'chs',
            'bknumber|账号'     => 'alphaDash',
            'address|地址'     => 'chsDash'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }
        $tokenint = AccBanks::getTokenintById($id);
        $hasPermission = $this->checkPermission($this->payload, $tokenint);
        if ($hasPermission) {
            $res = AccBanks::updBank($put, $id);
            if ($res) {
                $bank = AccBanks::bank($id);
                $this->jsonData['msg'] = '修改成功';
                $this->jsonData['data']['bank'] = $bank;
                return $this->jsonData;
            } else {
                $this->jsonData['status'] = 0;
                $this->jsonData['msg'] = '修改失败';
                return $this->jsonData;
            }

        } else {
            $this->jsonData['status'] = 403;
            $this->jsonData['msg'] = '您无权修改其他用户的信息';
            return $this->jsonData;
        }
    }

    /**
     * @param $id
     * @return array
     */
    public function delete($id)
    {
        $res = AccBanks::delById($id);
        if ($res) {
            return ['status' => 201, 'msg' => '删除成功'];
        } else {
            return ['status' => 0, 'msg' => '删除失败'];
        }
    }
}
