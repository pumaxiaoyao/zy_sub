<?php

namespace app\pk10\controller\admin;

use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use app\pk10\model\Pk10User;
use think\Request;

class Pk10UserController extends AdminBaseController
{
    /**
     * 显示资源列表
     *
     * @param Request $request
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $params = $request->param();
        // 页数
        $page = isset($params['page']) ? $params['page'] : 1;
        if ($page < 1) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg']    = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        // 查询条件
        $where['tokenint'] = [
            'in',
            $this->subUserTokens
        ];
        if (isset($params['username'])) {
            $where['username'] = [
                'like',
                $params['username']
            ];
        }

        // 下注列表
        $list = Pk10User::getList($page, $per_page, $where);
        foreach ($list as &$item) {
            $user = AccUsers::getUserByTokenint($item->tokenint);
            $item->user = $user;
            unset($item->tokenint);
        }
        $url = $request->baseUrl();

        $data = paginate_data($page, $per_page, $params, $where, $list, $url, Pk10User::class, 'getList');

        $this->jsonData['data'] = $data;

        return $this->jsonData;
    }


    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $record = Pk10User::getById($id);
        if (!$record) {
            return [
                'status' => 404,
                'msg'    => '该记录不存在'
            ];
        }
        return [
            'status' => 200,
            'msg'    => 'success',
            'data'   => [
                'list' => $record->toArray()
            ]
        ];
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int            $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function update(Request $request, $id)
    {
        $put = $request->put();
        $res = Pk10User::updateRecord($id, $put);
        if (!$res) {
            return [
                'status' => 0,
                'msg'    => '修改失败'
            ];
        }
        $new = Pk10User::getById($id);
        return [
            'status' => 0,
            'msg'    => '修改成功',
            'data'   => [
                'list' => $new
            ]
        ];
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    /*public function delete($id)
    {
        $exists = Pk10User::idExist($id);
        if (!$exists) {
            return ['status' => 404, 'msg' => '该记录不存在'];
        }
        $res = Pk10User::deleteRecord($id);
        if ($res) {
            return ['status' => 200, 'msg' => '删除成功'];
        }
        return ['status' => 0, 'msg' => '删除失败'];
    }*/
}
