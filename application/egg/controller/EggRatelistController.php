<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/30
 * Time: 16:10
 */

namespace app\egg\controller;


use app\auth\controller\BaseController;
use app\egg\model\EggCustomOdds;
use app\egg\model\EggOdds;
use app\egg\model\EggRatelist;

class EggRatelistController extends BaseController
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
        $ratelist = EggRatelist::getListByUser($tokenint);
        foreach ($ratelist as &$item) {
            $isCustom          = EggCustomOdds::isExists($item->ratewin_set, $tokenint);
            $item['is_custom'] = $isCustom ? 1 : 0;
            if ($isCustom) {
                $fs = EggCustomOdds::getFsByTableAndUser($item->ratewin_set, $tokenint, 3 - $this->user->type);
            } else {
                $fs = EggOdds::getFs($item->ratewin_set, 4 - $this->user->type);
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