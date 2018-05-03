<?php

namespace app\egg\controller\admin;

use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use app\egg\model\EggUser;
use think\Request;

class EggUserController extends AdminBaseController
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
        $list = EggUser::getList($page, $per_page, $where);
        foreach ($list as &$item) {
            $user = AccUsers::getUserByTokenint($item->tokenint);
            $item->user = $user;
            unset($item->tokenint);
        }
        $url = $request->baseUrl();

        $data = paginate_data($page, $per_page, $params, $where, $list, $url, EggUser::class, 'getList');

        $this->jsonData['data'] = $data;

        return $this->jsonData;
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     *
     * @return array
     * @throws \think\exception\DbException
     */


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
        $record = EggUser::get($id);
        $user   = AccUsers::getUserByTokenint($record->tokenint);
        unset($user->tokenint);
        unset($user->tokenext);
        unset($user->tokensup);
        unset($user->pwd_1);
        unset($user->pwd_2);
        unset($user->salt);
        $record->user = $user;
        unset($record->tokenint);
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
        $res = EggUser::updateRecord($id, $put);
        if (!$res) {
            return [
                'status' => 0,
                'msg'    => '修改失败'
            ];
        }
        $new = EggUser::getById($id);
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
        $exists = EggUser::idExist($id);
        if (!$exists) {
            return ['status' => 404, 'msg' => '该记录不存在'];
        }
        $res = EggUser::deleteRecord($id);
        if ($res) {
            return ['status' => 200, 'msg' => '删除成功'];
        }
        return ['status' => 0, 'msg' => '删除失败'];
    }*/
}
