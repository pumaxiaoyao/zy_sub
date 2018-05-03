<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/30
 * Time: 16:10
 */

namespace app\cake\controller;


use app\auth\controller\BaseController;
use app\cake\model\CakeCustomOdds;
use app\cake\model\CakeOdds;
use app\cake\model\CakeRatelist;

class CakeRatelistController extends BaseController
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
        $ratelist = CakeRatelist::getListByUser($tokenint);
        foreach ($ratelist as &$item) {
            $isCustom          = CakeCustomOdds::isExists($item->ratewin_set, $tokenint);
            $item['is_custom'] = $isCustom ? 1 : 0;
            if ($isCustom) {
                $fs = CakeCustomOdds::getFsByTableAndUser($item->ratewin_set, $tokenint, 3 - $this->user->type);
            } else {
                $fs = CakeOdds::getFs($item->ratewin_set, 4 - $this->user->type);
            }
            $item['fs'] = $fs * 100 . "%";
        }
        $this->jsonData['data']['ratelist'] = $ratelist;
        return $this->jsonData;
    }
}