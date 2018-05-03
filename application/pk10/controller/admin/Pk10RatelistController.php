<?php

namespace app\pk10\controller\admin;

use app\api\model\AccUsers;
use app\auth\controller\AdminBaseController;
use app\pk10\model\Base;
use app\pk10\model\Pk10CustomOdds;
use app\pk10\model\Pk10Ratelist;
use think\Request;

class Pk10RatelistController extends AdminBaseController
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
            $this->jsonData['msg'] = '页码不能小于1';
            return $this->jsonData;
        }
        // 每页显示数量
        $per_page = isset($params['per_page']) ? $params['per_page'] : 15;

        // 查询条件
        $where         = [];
        $where['type'] = [
            '=',
            0
        ];
        if (isset($params['username'])) {
            $where['username'] = [
                'like',
                $params['username']
            ];
        }

        // 下注列表
        $list = AccUsers::getPk10Ratelist($page, $per_page, $where);
        foreach ($list as &$item) {
            $item->pk10_ratelist;
            unset($item->tokenint);
            foreach ($item->pk10_ratelist as $item2) {
                unset($item2->tokenint);
            }
        }
        $url = $request->baseUrl();

        $data = paginate_data($page, $per_page, $params, $where, $list, $url, AccUsers::class, 'getPk10Ratelist');

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
    public function save(Request $request)
    {
        $post = $request->post();
        if (!AccUsers::userExist($post['user_id'])) {
            return [
                'status' => 0,
                'msg'    => '此用户不存在'
            ];
        }

        // 检查赔率表是否存在
        $tableName   = 'pk10_' . strtolower(trim($post['ratewin_name']));
        $tableExists = Base::tableExists($tableName);
        if (!$tableExists) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg']    = '没有此赔率表';
            return $this->jsonData;
        }

        // 重复检查
        $duplicate = Pk10Ratelist::checkRateDuplicate($post);
        if ($duplicate) {
            return [
                'status' => 0,
                'msg'    => '请匆重复添加'
            ];
        }
        $rate_id = Pk10Ratelist::addRate($post);
        if (!$rate_id) {
            return [
                'status' => 0,
                'msg'    => '添加用户盘口失败'
            ];
        }
        $rate = Pk10Ratelist::getRateById($rate_id);
        return [
            'status' => 200,
            'msg'    => '添加用户盘口成功',
            'data'   => [
                'rate' => $rate
            ]
        ];
    }

    /**
     * 指定用户可选盘口列表
     *
     * @param  int $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $tokenint = AccUsers::getTokenint($id);
        $ratelist = Pk10Ratelist::getListByUser($tokenint);
        foreach ($ratelist as &$item) {
            $isCustom          = Pk10CustomOdds::isExists($item->ratewin_set, $tokenint);
            $item['is_custom'] = $isCustom ? 1 : 0;
        }
        return [
            'status' => 200,
            'msg'    => 'success',
            'data'   => [
                'ratelist' => $ratelist
            ]
        ];
    }

    /**
     * 指定用户可选盘口列表
     *
     * @param  int $id
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $ratelist = Pk10Ratelist::getRateById($id);
        if (!$ratelist) {
            return [
                'status' => 404,
                'msg'    => '记录不存在'
            ];
        }
        return [
            'status' => 200,
            'msg'    => 'success',
            'data'   => [
                'ratelist' => $ratelist->toArray()
            ]
        ];
    }

    /**
     * 修改用户盘口
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
        $res = Pk10Ratelist::updateRate($put, $id);
        if (!$res) {
            return [
                'status' => 0,
                'msg'    => '修改用户盘口失败'
            ];
        }
        $rate = Pk10Ratelist::getRateById($id);
        return [
            'status' => 201,
            'msg'    => '修改用户盘口成功',
            'data'   => [
                'rate' => $rate
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
    public function delete($id)
    {
        if (!Pk10Ratelist::isExist($id)) {
            return [
                'status' => 404,
                'msg'    => '记录不存在'
            ];
        }
        $res = Pk10Ratelist::deleteById($id);
        if (!$res) {
            return [
                'status' => 0,
                'msg'    => '删除失败'
            ];
        }
        return [
            'status' => 200,
            'msg'    => '删除成功'
        ];
    }
}
