<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/4/12
 * Time: 17:45
 */

namespace app\api\controller\admin;


use app\api\model\AccMychg;
use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use think\Request;

class AccMychgController extends AdminBaseController
{
    /**
     * @param Request $request
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $get   = $request->only([
            'page',
            'per_page',
            'user_id'
        ], 'get');
        $error = $this->validate($get, [
            'page|页码'       => 'number|egt:1',
            'per_page|每页显示' => 'number|egt:5',
            'user_id|用户ID'  => 'number|egt:1',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg']    = $error;
            return $this->jsonData;
        }

        // 页数
        $page = isset($get['page']) ? $get['page'] : 1;
        if ($page < 1) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg']    = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($get['per_page']) ? $get['per_page'] : 15;

        $where = [];
        if (isset($get['user_id'])) {
            $tokenint          = AccUsers::getTokenint($get['user_id']);
            $where['tokenint'] = $tokenint;
        }
        $list = AccMychg::getAll($page, $per_page, $where);
        $url  = $request->baseUrl();
        $data = paginate_data($page, $per_page, $get, $where, $list, $url, AccMychg::class, 'getAll');

        $this->jsonData['data']['chgs'] = $data;

        return $this->jsonData;
    }
}