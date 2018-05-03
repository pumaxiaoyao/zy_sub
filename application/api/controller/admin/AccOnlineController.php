<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/1
 * Time: 16:30
 */

namespace app\api\controller\admin;


use app\api\model\AccOnline;
use app\auth\controller\AdminBaseController;

class AccOnlineController extends AdminBaseController
{
    /**
     * 在线用户列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $onlines = AccOnline::allOnlineUsers();
        $onlineNum = count($onlines);
        $this->jsonData['data']['online_num'] = $onlineNum;
        $this->jsonData['data']['onlines'] = $onlines;
        return $this->jsonData;
    }

}