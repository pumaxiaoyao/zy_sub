<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/30
 * Time: 16:10
 */

namespace app\pk10\controller;


use app\auth\controller\BaseController;
use app\pk10\model\Pk10CustomOdds;
use app\pk10\model\Pk10Odds;
use app\pk10\model\Pk10Ratelist;

class Pk10RatelistController extends BaseController
{
    /**
     * 指定用户可选盘口列表
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function read()
    {
        $tokenint = $this->user->tokenint;
        $ratelist = Pk10Ratelist::getListByUser($tokenint);
        foreach ($ratelist as &$item) {
            $isCustom          = Pk10CustomOdds::isExists($item->ratewin_set, $tokenint);
            $item['is_custom'] = $isCustom ? 1 : 0;
            if ($isCustom) {
                $fs = Pk10CustomOdds::getFsByTableAndUser($item->ratewin_set, $tokenint, 3 - $this->user->type);
            } else {
                $fs = Pk10Odds::getFs($item->ratewin_set, 4 - $this->user->type);
            }
            $item['fs'] = $fs * 100 . "%";
        }
        return [
            'status' => 200,
            'msg'    => 'success',
            'data'   => [
                'ratelist' => $ratelist
            ]
        ];
    }
}