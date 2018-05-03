<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/3
 * Time: 11:57
 */

namespace app\cqssc\controller;


use app\auth\controller\BaseController;
use app\cqssc\model\SscCustomOdds;
use app\cqssc\model\SscOdds;
use app\cqssc\model\SscRatelist;

class SscController extends BaseController
{
    /**
     * 获取用户专属赔率
     *
     * @param $type
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function odds($type)
    {
        if (!is_numeric($type)) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg']    = '参数 type 错误！';
            return $this->jsonData;
        }
        $tokenint   = $this->user->tokenint;
        $get        = request()->only(['pan'], 'get');
        $defaultPan = SscRatelist::getPanByUser($tokenint);
        $pan        = isset($get['pan']) ? trim($get['pan']) : $defaultPan;
        $panTable   = 'ssc_' . strtolower($pan);

        // 检查用户该赔率表是否有定制赔率
        $customExists = SscCustomOdds::isExists($panTable, $tokenint);
        if ($customExists) {
            $odds = SscCustomOdds::getOddsByTableAndUser($panTable, $tokenint);
        } else {
            $odds = SscOdds::getAll($panTable)->toArray();
        }
        $ret = $this->organizeOdds($odds, $type);
        if (empty($ret)) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg']    = '未知错误！';
            return $this->jsonData;
        }
        $this->jsonData['data']['pan']  = $pan;
        $this->jsonData['data']['odds'] = $ret;
        return $this->jsonData;
    }

    /**
     * 获取对应的赔率组合
     *
     * @param $odds
     * @param $type
     *
     * @return array
     */
    private function organizeOdds($odds, $type)
    {
        switch ($type) {
            // 第一球
            case 1:
                $odds = $this->organizeBall($odds, 1);
                break;
            // 第二球
            case 2:
                $odds = $this->organizeBall($odds, 2);
                break;
            // 第三球
            case 3:
                $odds = $this->organizeBall($odds, 3);
                break;
            // 第四球
            case 4:
                $odds = $this->organizeBall($odds, 4);
                break;
            // 第五球
            case 5:
                $odds = $this->organizeBall($odds, 5);
                break;
            // 两面盘
            case 6:
                $odds = $this->organizeHalf($odds);
                break;
            // 数字盘
            case 7:
                $odds = $this->organizeDigit($odds);
                break;
            default:
                $odds = $this->allOdds($odds);
                break;
        }
        return $odds;
    }

    /**
     * 所有赔率
     *
     * @param [type] $odds
     * @return array
     */
    private function allOdds($odds)
    {
        $odds1 = $this->organizeDigit($odds);
        $odds2 = $this->organizeHalf($odds);
        $odds3 = $this->digitPublicOdds($odds);
        $odds = array_merge($odds1, $odds2, $odds3);
        return $odds;
    }

    /**
     * 组织单球赔率组合
     *
     * @param $odds
     * @param $ballNum
     *
     * @return array
     */
    private function organizeBall($odds, $ballNum)
    {
        $ball_odds                                = $this->digitPublicOdds($odds);
        $ball_digit_odds                          = $this->getDigitalOdds($odds, $ballNum);
        $ball_half_odds                           = $this->getHalfOdds($odds, $ballNum);
        $ball_odds['ball_' . $ballNum . '_digit'] = $ball_digit_odds;
        $ball_odds['ball_' . $ballNum . '_half']  = $ball_half_odds;
        return $ball_odds;
    }

    /**
     * 组织两面盘赔率组合
     *
     * @param $odds
     *
     * @return array
     */
    private function organizeHalf($odds)
    {
        $ball_1_odds      = $this->getHalfOdds($odds, 1);
        $ball_2_odds      = $this->getHalfOdds($odds, 2);
        $ball_3_odds      = $this->getHalfOdds($odds, 3);
        $ball_4_odds      = $this->getHalfOdds($odds, 4);
        $ball_5_odds      = $this->getHalfOdds($odds, 5);
        $dragon_and_tiger = $this->dragonAndTiger($odds);
        return [
            'ball_1_half'      => $ball_1_odds,
            'ball_2_half'      => $ball_2_odds,
            'ball_3_half'      => $ball_3_odds,
            'ball_4_half'      => $ball_4_odds,
            'ball_5_half'      => $ball_5_odds,
            'dragon_and_tiger' => $dragon_and_tiger
        ];
    }

    private function organizeDigit($odds)
    {
        $ball_1_odds = $this->getDigitalOdds($odds, 1);
        $ball_2_odds = $this->getDigitalOdds($odds, 2);
        $ball_3_odds = $this->getDigitalOdds($odds, 3);
        $ball_4_odds = $this->getDigitalOdds($odds, 4);
        $ball_5_odds = $this->getDigitalOdds($odds, 5);
        return [
            'ball_1_digit' => $ball_1_odds,
            'ball_2_digit' => $ball_2_odds,
            'ball_3_digit' => $ball_3_odds,
            'ball_4_digit' => $ball_4_odds,
            'ball_5_digit' => $ball_5_odds,
        ];
    }

    /**
     *
     * @param $odds
     *
     * @return array
     */
    private function digitPublicOdds($odds)
    {
        $dragon_and_tiger_odds = $this->dragonAndTiger($odds);
        $front_3_odds          = $this->threeBalls($odds, 10);
        $medium_3_odds         = $this->threeBalls($odds, 11);
        $end_3_odds            = $this->threeBalls($odds, 12);
        return [
            'dragon_and_tiger' => $dragon_and_tiger_odds,
            'front_3'          => $front_3_odds,
            'medium_3'         => $medium_3_odds,
            'end_3'            => $end_3_odds
        ];
    }

    /**
     * 单球数字赔率
     *
     * @param $odds    SscOdds 赔率总表
     * @param $ballNum integer 第几球
     *
     * @return mixed
     */
    private function getDigitalOdds($odds, $ballNum)
    {
        $index  = $ballNum + 3;
        $record = $odds[$index];
        array_splice($record, -5, 5);
        fix_odds_array($record);
        return $record;
    }

    /**
     * 单球两面赔率
     *
     * @param $odds    SscOdds 赔率总表
     * @param $ballNum integer 第几球
     *
     * @return mixed
     */
    private function getHalfOdds($odds, $ballNum)
    {
        $index  = $ballNum + 3;
        $record = $odds[$index];
        array_splice($record, 5, 10);
        array_pop($record);
        fix_odds_array($record);
        return $record;
    }

    /**
     * 单球两面赔率
     *
     * @param $odds SscOdds 赔率总表
     *
     * @return mixed
     */
    private function dragonAndTiger($odds)
    {
        $record = $odds[9];
        $record = array_filter($record);
        fix_odds_array($record);
        return $record;
    }

    /**
     * @param $odds SscOdds 赔率总表
     * @param $id   integer 所在记录 id
     *
     * @return mixed
     */
    private function threeBalls($odds, $id)
    {
        $record = $odds[$id];
        $record = array_filter($record);
        fix_odds_array($record);
        return $record;
    }

}