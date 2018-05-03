<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/4/20
 * Time: 8:46
 */

namespace app\api\controller;


use app\api\model\AccLogin;
use app\auth\controller\BaseController;
use think\Request;

class AccLoginController extends BaseController
{
    /**
     * 用户登录记录列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $params = $request->only(['page', 'per_page']);

        $error = $this->validate($params, [
            'page|页码'             => 'number|egt:1',
            'per_page|每页显示'     => 'number|egt:5',
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

        $list = AccLogin::getLogins($page, $per_page, $where);
        $url = $request->baseUrl();
        $data = paginate_data($page, $per_page, $params, $where, $list, $url, AccLogin::class, 'getLogins');

        $this->jsonData['data'] = $data;

        return $this->jsonData;
    }
}