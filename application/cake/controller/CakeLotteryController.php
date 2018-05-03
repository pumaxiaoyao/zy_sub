<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/6
 * Time: 15:58
 */

namespace app\cake\controller;


use app\auth\controller\BaseController;
use app\cake\model\CakeLottery;
use think\Request;

class CakeLotteryController extends BaseController
{

    /**
     * @param Request $request
     *
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public function history(Request $request)
    {
        $params = $request->param();
        // 页数
        $page = isset($params['page']) ? $params['page'] : 1;
        if ($page < 1) {
            $this->jsonData['msg'] = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        // 查询条件
        $where = [];
        if (isset($params['expect'])) {
            $where['expect'] = $params['expect'];
        }
        if (isset($params['is_lottery'])) {
            $where['is_lottery'] = $params['is_lottery'];
        }
        if (isset($params['range'])) {
            $timeRange = get_time_range($params['range']);
        } else {
            $timeRange = get_time_range('today');            
        }
        $where['opentimestamp'] = ['between' , [$timeRange['start'], $timeRange['end']]];
        // 开奖历史
        $list = CakeLottery::history($page, $per_page, $where);

        // 当前页显示的列表
        $kaijiangCtrl = new KaijiangController();
        foreach ($list as $key => &$item) {
            // 解析开奖内容
            $item['open_codes'] = explode(',', $item['opencode']);
            $item['details'] = $kaijiangCtrl->getCakeResult($item['opencode']);
            $res = CakeLottery::expectLotteried($item['expect']);
            if ($res) {
                $is_lottery = 1;
            } else {
                $is_lottery = 0;
            }
            $item['is_lottery'] = $is_lottery;
            $item['code'] = 'cake';
        }
        $url = $request->baseUrl();
        $data = paginate_data($page, $per_page, $params, $where, $list, $url, CakeLottery::class, 'history');

        $this->jsonData['data'] = $data;
        return $this->jsonData;
    }
}