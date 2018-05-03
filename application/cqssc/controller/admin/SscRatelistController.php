<?php

namespace app\cqssc\controller\admin;

use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use app\cqssc\model\SscRatelist;
use think\Request;

class SscRatelistController extends AdminBaseController
{
    /**
     * 显示资源列表
     *
     * @param Request $request
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
            $this->jsonData['msg'] = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        // 查询条件
        $where = [];
        $where['id'] = ['>', 1];
        if (isset($params['username'])) {
            $where['username'] = ['like', $params['username']];
        }

        // 下注列表
        $list = AccUsers::getSscRatelist($page, $per_page, $where);
        foreach ($list as &$item) {
            $item->ssc_ratelist;
            unset($item->tokenint);
            foreach ($item->ssc_ratelist as $item2) {
                unset($item2->tokenint);
            }
        }
        $url = $request->path();

        $data = paginate_data($page, $per_page, $params, $where, $list, $url, AccUsers::class, 'getSscRatelist');

        $this->jsonData['data'] = $data;

        return $this->jsonData;

    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $post = $request->post();
        // 重复检查
        $duplicate = SscRatelist::checkRateDuplicate($post);
        if ($duplicate) {
            return ['status' => 0, 'msg' => '请匆重复添加'];
        }
        $rate_id = SscRatelist::addRate($post);
        if (!$rate_id) {
            return ['status' => 0, 'msg' => '添加用户盘口失败'];
        }
        $rate = SscRatelist::getRateById($rate_id);
        return ['status' => 200, 'msg' => '添加用户盘口成功', 'data' => [
            'rate' => $rate
        ]];
    }

    /**
     * 指定用户可选盘口列表
     *
     * @param  int $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $tokenint = AccUsers::getTokenint($id);
        $ratelist = SscRatelist::getListByUser($tokenint);
        return ['status' => 200, 'msg' => 'success', 'data' => [
            'ratelist' => $ratelist
        ]];
    }

    /**
     * 指定用户可选盘口列表
     *
     * @param  int $id
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $ratelist = SscRatelist::getRateById($id);
        if (!$ratelist) {
            return ['status' => 404, 'msg' => '记录不存在'];
        }
        return ['status' => 200, 'msg' => 'success', 'data' => [
            'ratelist' => $ratelist->toArray()
        ]];
    }

    /**
     * 修改用户盘口
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function update(Request $request, $id)
    {
        $put = $request->put();
        $res = SscRatelist::updateRate($put, $id);
        if (!$res) {
            return ['status' => 0, 'msg' => '修改用户盘口失败'];
        }
        $rate = SscRatelist::getRateById($id);
        return ['status' => 201, 'msg' => '修改用户盘口成功', 'data' => [
            'rate' => $rate
        ]];
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function delete($id)
    {
        if (!SscRatelist::isExist($id)) {
            return ['status' => 404, 'msg' => '记录不存在'];
        }
        $res = SscRatelist::deleteById($id);
        if (!$res) {
            return ['status' => 0, 'msg' => '删除失败'];
        }
        return ['status' => 200, 'msg' => '删除成功'];
    }
}
