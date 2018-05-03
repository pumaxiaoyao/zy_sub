<?php

namespace app\api\controller;

use app\api\model\AccMoney;
use app\api\model\AccUsers;
use app\auth\controller\BaseController;
use think\Request;

class AccMoneyController extends BaseController
{

    /**
     * 查看用户资金详情
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read()
    {
        $tokenint = $this->user->tokenint;
        $money = AccMoney::getMoneyByUser($tokenint);
        if (is_null($money)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '暂无资金记录，请充值';
            return $this->jsonData;
        } else {
            $this->jsonData['data']['money'] = $money;
            return $this->jsonData;
        }
    }

    /**
     * 供子盘获取金额信息
     * @param Request $request
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMoney(Request $request)
    {
        $get = $request->get();
        $error = $this->validate($get, [
            'tokensup'     => 'require|alphaNum|length:64',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $tokensup = $get['tokensup'];
        $tokenint = AccUsers::getTokenintByTokensup($tokensup);
        $money = AccMoney::getMoneyByUser($tokenint);
        return $money;
    }
}
