<?php

namespace app\api\controller;

use app\api\model\AccMychg;
use app\api\model\AccUsers;
use app\auth\controller\BaseController;
use think\helper\Time;
use think\Request;

class AccMychgController extends BaseController
{

    /**
     * 用户资金变动记录
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $params = $request->only(['page', 'per_page', 'type', 'range']);

        $error = $this->validate($params, [
            'page|页码'             => 'number|egt:1',
            'per_page|每页显示'     => 'number|egt:5',
            'type|收支类型'         => 'number|between:1,7',
            'range|查询期间'        => 'alphaDash'
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }


        // 页数
        $page = isset($params['page']) ? $params['page'] : 1;
        if ($page < 1) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        $where['tokenint'] = $this->user->tokenint;
        // 查询时间范围
        if (isset($params['range'])) {
            $timeRange = get_time_range($params['range']);
            $where['opr_time'] = ['between', [$timeRange['start_date'], $timeRange['end_date']]];
        }
        // 查询收支类型
        if (isset($params['type'])) {
            $type = trim($params['type']);
            if (in_array($type, [CHG_TOPUP, CHG_WITHDRAW, CHG_BET, CHG_BET_LUCKY, CHG_BET_FS, CHG_BET_LS, CANCEL_ORDER])) {
                $where['type'] = ['=', $type];
            }
        }
        $list = AccMychg::getChgsByUser($page, $per_page, $where);
        $url = $request->baseUrl();
        $data = paginate_data($page, $per_page, $params, $where, $list, $url, AccMychg::class, 'getChgsByUser');

        $this->jsonData['data']['chgs'] = $data;

        return $this->jsonData;
    }

    /**
     * 用户今日盈亏
     * @return mixed
     */
    /*public function moneySummary()
    {
        $where['tokenint'] = ['=', $this->user->tokenint];
        list($start, $end) = Time::today();
        $where['opr_time'] = ['between', [date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]];
        $where['type'] = ['in', [3, 4]];
        $yk = AccMychg::todayYk($where);
        $this->jsonData['data']['yk'] = $yk;
        return $this->jsonData;
    }*/

}
