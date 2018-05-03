<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/3
 * Time: 11:57
 */

namespace app\pk10\controller;


use app\auth\controller\BaseController;
use app\pk10\model\Pk10CustomOdds;
use app\pk10\model\Pk10Odds;
use app\pk10\model\Pk10Ratelist;

class Pk10Controller extends BaseController
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
            return [
                'status' => 0,
                'msg'    => '参数 type 错误！'
            ];
        }
        $tokenint   = $this->user->tokenint;
        $get        = request()->only(['pan'], 'get');
        $defaultPan = Pk10Ratelist::getPanByUser($tokenint);
        $pan        = isset($get['pan']) ? trim($get['pan']) : $defaultPan;
        $panTable   = 'pk10_' . strtolower($pan);

        // 检查用户该赔率表是否有定制赔率
        $customExists = Pk10CustomOdds::isExists($panTable, $tokenint);
        if ($customExists) {
            $odds = Pk10CustomOdds::getOddsByTableAndUser($panTable, $tokenint);
        } else {
            $odds = Pk10Odds::getAll($panTable)->toArray();
        }
        $ret = $this->organizeOdds($odds, $type);
        if (empty($ret)) {
            return [
                'status' => 0,
                'msg'    => '未知错误！',
                'data'   => [
                    'odds' => $ret
                ]
            ];
        }
        return [
            'status' => 200,
            'msg'    => 'success',
            'data'   => [
                'pan'  => $pan,
                'odds' => $ret
            ]
        ];
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
            // 单球1-10
            case 1:
                $odds = $this->organizeDigit($odds);
                break;
            // 两面盘
            case 2:
                $odds = $this->organizeHalf($odds);
                break;
            // 冠亚军和
            case 3:
                $odds = $this->organizeSum($odds);
                break;
            // 三、四、五、六名
            case 4:
                $odds = $this->organize36($odds);
                break;
            // 七、八、九、十名
            case 5:
                $odds = $this->organize710($odds);
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
        $odds3 = $this->organizeSum($odds);
        unset($odds3['ball_1']);
        unset($odds3['ball_2']);
        $odds = array_merge($odds1, $odds2, $odds3);
        return $odds;
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
        $ball_1_odds  = $this->getHalfOdds($odds, 1);
        $ball_2_odds  = $this->getHalfOdds($odds, 2);
        $ball_3_odds  = $this->getHalfOdds($odds, 3);
        $ball_4_odds  = $this->getHalfOdds($odds, 4);
        $ball_5_odds  = $this->getHalfOdds($odds, 5);
        $ball_6_odds  = $this->getHalfOdds($odds, 6);
        $ball_7_odds  = $this->getHalfOdds($odds, 7);
        $ball_8_odds  = $this->getHalfOdds($odds, 8);
        $ball_9_odds  = $this->getHalfOdds($odds, 9);
        $ball_10_odds = $this->getHalfOdds($odds, 10);
        return [
            'ball_1_half'  => $ball_1_odds,
            'ball_2_half'  => $ball_2_odds,
            'ball_3_half'  => $ball_3_odds,
            'ball_4_half'  => $ball_4_odds,
            'ball_5_half'  => $ball_5_odds,
            'ball_6_half'  => $ball_6_odds,
            'ball_7_half'  => $ball_7_odds,
            'ball_8_half'  => $ball_8_odds,
            'ball_9_half'  => $ball_9_odds,
            'ball_10_half' => $ball_10_odds,
        ];
    }

    /**
     * 组织数字盘赔率组合
     *
     * @param $odds
     *
     * @return array
     */
    private function organizeDigit($odds)
    {
        $ball_1_odds  = $this->getDigitalOdds($odds, 1);
        $ball_2_odds  = $this->getDigitalOdds($odds, 2);
        $ball_3_odds  = $this->getDigitalOdds($odds, 3);
        $ball_4_odds  = $this->getDigitalOdds($odds, 4);
        $ball_5_odds  = $this->getDigitalOdds($odds, 5);
        $ball_6_odds  = $this->getDigitalOdds($odds, 6);
        $ball_7_odds  = $this->getDigitalOdds($odds, 7);
        $ball_8_odds  = $this->getDigitalOdds($odds, 8);
        $ball_9_odds  = $this->getDigitalOdds($odds, 9);
        $ball_10_odds = $this->getDigitalOdds($odds, 10);
        return [
            'ball_1_digit'  => $ball_1_odds,
            'ball_2_digit'  => $ball_2_odds,
            'ball_3_digit'  => $ball_3_odds,
            'ball_4_digit'  => $ball_4_odds,
            'ball_5_digit'  => $ball_5_odds,
            'ball_6_digit'  => $ball_6_odds,
            'ball_7_digit'  => $ball_7_odds,
            'ball_8_digit'  => $ball_8_odds,
            'ball_9_digit'  => $ball_9_odds,
            'ball_10_digit' => $ball_10_odds,
        ];
    }


    /**
     * 单球数字赔率
     *
     * @param $odds    Pk10Odds 赔率总表
     * @param $ballNum integer 第几球
     *
     * @return mixed
     */
    private function getDigitalOdds($odds, $ballNum)
    {
        $index  = $ballNum + 3;
        $record = $odds[$index];
        array_splice($record, -7, 7);
        fix_odds_array($record);
        return $record;
    }

    /**
     * 单球两面赔率
     *
     * @param $odds    Pk10Odds 赔率总表
     * @param $ballNum integer 第几球
     *
     * @return mixed
     */
    private function getHalfOdds($odds, $ballNum)
    {
        $index  = $ballNum + 3;
        $record = $odds[$index];
        array_splice($record, 5, 10);
        $record = array_filter($record);
        fix_odds_array($record);
        return $record;
    }


    /**
     * 冠亚军和
     *
     * @param $odds
     *
     * @return array
     */
    public function organizeSum($odds)
    {
        // 冠亚军和（特码）
        $sum_digit = array_filter($odds[14]);
        fix_odds_array($sum_digit);
        // 冠亚军和（两面）
        $sum_half = array_filter($odds[15]);
        fix_odds_array($sum_half);
        // 冠军
        $ball_1_digit = $this->getDigitalOdds($odds, 1);
        $ball_1_half  = $this->getHalfOdds($odds, 1);
        // 亚军
        $ball_2_digit = $this->getDigitalOdds($odds, 2);
        $ball_2_half  = $this->getHalfOdds($odds, 2);
        return [
            'sum_digit' => $sum_digit,
            'sum_half'  => $sum_half,
            'ball_1'    => [
                'ball_1_digit' => $ball_1_digit,
                'ball_1_half'  => $ball_1_half,
            ],
            'ball_2'    => [
                'ball_2_digit' => $ball_2_digit,
                'ball_2_half'  => $ball_2_half,
            ],
        ];
    }

    public function organize36($odds)
    {
        // 第三名
        $ball_3_digit = $this->getDigitalOdds($odds, 3);
        $ball_3_half  = $this->getHalfOdds($odds, 3);
        // 第四名
        $ball_4_digit = $this->getDigitalOdds($odds, 4);
        $ball_4_half  = $this->getHalfOdds($odds, 4);
        // 第三名
        $ball_5_digit = $this->getDigitalOdds($odds, 5);
        $ball_5_half  = $this->getHalfOdds($odds, 5);
        // 第四名
        $ball_6_digit = $this->getDigitalOdds($odds, 6);
        $ball_6_half  = $this->getHalfOdds($odds, 6);
        return [
            'ball_3' => [
                'ball_3_digit' => $ball_3_digit,
                'ball_3_half'  => $ball_3_half,
            ],
            'ball_4' => [
                'ball_4_digit' => $ball_4_digit,
                'ball_4_half'  => $ball_4_half,
            ],
            'ball_5' => [
                'ball_5_digit' => $ball_5_digit,
                'ball_5_half'  => $ball_5_half,
            ],
            'ball_6' => [
                'ball_6_digit' => $ball_6_digit,
                'ball_6_half'  => $ball_6_half,
            ],
        ];
    }

    public function organize710($odds)
    {
        // 第三名
        $ball_7_digit = $this->getDigitalOdds($odds, 7);
        $ball_7_half  = $this->getHalfOdds($odds, 7);
        // 第四名
        $ball_8_digit = $this->getDigitalOdds($odds, 8);
        $ball_8_half  = $this->getHalfOdds($odds, 8);
        // 第三名
        $ball_9_digit = $this->getDigitalOdds($odds, 9);
        $ball_9_half  = $this->getHalfOdds($odds, 9);
        // 第四名
        $ball_10_digit = $this->getDigitalOdds($odds, 10);
        $ball_10_half  = $this->getHalfOdds($odds, 10);
        return [
            'ball_7'  => [
                'ball_7_digit' => $ball_7_digit,
                'ball_7_half'  => $ball_7_half,
            ],
            'ball_8'  => [
                'ball_8_digit' => $ball_8_digit,
                'ball_8_half'  => $ball_8_half,
            ],
            'ball_9'  => [
                'ball_9_digit' => $ball_9_digit,
                'ball_9_half'  => $ball_9_half,
            ],
            'ball_10' => [
                'ball_10_digit' => $ball_10_digit,
                'ball_10_half'  => $ball_10_half,
            ],
        ];
    }

}