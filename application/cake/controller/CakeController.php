<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/3
 * Time: 11:57
 */

namespace app\cake\controller;


use app\auth\controller\BaseController;
use app\cake\model\CakeCustomOdds;
use app\cake\model\CakeOdds;
use app\cake\model\CakeRatelist;

class CakeController extends BaseController
{
    /**
     * 获取用户专属赔率
     * @return array
     * @throws \think\db\exception\BindParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function odds()
    {
        $tokenint = $this->user->tokenint;
        $get = request()->only(['pan'], 'get');
        $defaultPan = CakeRatelist::getPanByUser($tokenint);
        $pan = isset($get['pan']) ? trim($get['pan']) : $defaultPan;
        $panTable = 'cake_' . strtolower($pan);

        // 检查用户该赔率表是否有定制赔率
        $customExists = CakeCustomOdds::isExists($panTable, $tokenint);
        if ($customExists) {
            $odds = CakeCustomOdds::getOddsByTableAndUser($panTable, $tokenint);
        } else {
            $odds = CakeOdds::getAll($panTable)->toArray();
        }
        $ret = $this->organizeOdds($odds);
        if (empty($ret)) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '未知错误！';
            $this->jsonData['data']['odds'] = $ret;
            return $this->jsonData;
        }
        $this->jsonData['data']['odds'] = $ret;
        $this->jsonData['data']['pan'] = $pan;
        return $this->jsonData;
    }

    /**
     * 获取对应的赔率组合
     *
     * @param $odds
     *
     * @return array
     */
    private function organizeOdds($odds)
    {
        // 第一球
        $ball_1_odds = $this->organizeBall($odds, 5);
        // 第二球
        $ball_2_odds = $this->organizeBall($odds, 6);
        // 第三球
        $ball_3_odds = $this->organizeBall($odds, 7);
        // 第四球
        $ball_4_odds = $this->organizeBall($odds, 8);
        // 第五球
        $ball_5_odds = $this->organizeBall($odds, 9);
        return [
            'ball_1' => $ball_1_odds,
            'ball_2' => $ball_2_odds,
            'ball_3' => $ball_3_odds,
            'ball_4' => $ball_4_odds,
            'ball_5' => $ball_5_odds,
        ];
    }

    /**
     * @param array $odds
     * @param       $id
     *
     * @return array
     */
    private function organizeBall($odds, $id)
    {
        $index = $id - 1;
        $record = $odds[$index];
        $ball_odds = array_filter($record);
        fix_odds_array($ball_odds);
        return $ball_odds;
    }


}