<?php

namespace app\api\controller\admin;

use app\api\model\AccMoney;
use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use GuzzleHttp\Client;
use think\Request;

class AccMoneyController extends AdminBaseController
{

    /**
     * 用户资金列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $moneyList = AccMoney::getList();
        if (empty($moneyList)) {
            $this->jsonData['status'] = 404;
            $this->jsonData['msg'] = '暂无用户资金记录';
            return $this->jsonData;
        } else {
            $this->jsonData['data']['money_list'] = $moneyList;
            return $this->jsonData;
        }
    }
}
