<?php

namespace app\cake\controller\admin;

use app\auth\controller\AdminBaseController;
use app\cake\model\CakeCustomOdds;
use app\cake\model\CakeOdds;
use think\Request;

class CakeCustomOddsController extends AdminBaseController
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 添加用户定制赔率
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $post       = $request->only([
            'user_id',
            'table',
            'odds'
        ], 'post');
        $user_id    = trim($post['user_id']);
        $tokenint   = get_user_tokenint_by_id($user_id);
        $baseTable  = trim($post['table']);
        $customOdds = $post['odds'];
        // 检查数值是否比直属上级高，高则不能通过
        // 直属上级就是自己
        // 先找自己的定制赔率
        //        $selfCustomOdds = CakeCustomOdds::getOddsByTableAndUser($baseTable, $this->user->tokenint);
        //        if ($selfCustomOdds) {
        //            $selfOdds = json_decode($selfCustomOdds, true);
        //        } else {
        // 如果定制赔率不存在，则查找基础赔率表数据

        //        查找基础赔率表数据
        $selfOdds = CakeOdds::getOddsByTable($baseTable);
        //        }
        $invalid = compare_odds($selfOdds->toArray(), $customOdds);
        if (!empty($invalid)) {
            // 返回非法数据坐标
            $this->jsonData['status']          = 0;
            $this->jsonData['msg']             = 'error';
            $this->jsonData['data']['invalid'] = $invalid;
            return $this->jsonData;
        }

        // 存在性检查，防止重复添加
        $exists = CakeCustomOdds::isExists($baseTable, $tokenint);
        if ($exists) {
            try {

                $customOddsModel       = CakeCustomOdds::getOneByTableAndUser($baseTable, $tokenint);
                $customOddsModel->odds = json_encode($customOdds);
                $result                = $customOddsModel->save();
                if (!$result) {
                    $this->jsonData['status'] = 0;
                    $this->jsonData['msg']    = '修改定制赔率失败';
                    return $this->jsonData;
                }
                $this->jsonData['msg'] = '修改定制赔率成功';
                return $this->jsonData;
            } catch (\Exception $e) {
                dump($e->getMessage());
                exit;
            }
        }

        $customOddsModel            = new CakeCustomOdds();
        $customOddsModel->tokenint  = $tokenint;
        $customOddsModel->base_odds = $baseTable;
        $customOddsModel->odds      = json_encode($customOdds);
        $result                     = $customOddsModel->save();
        if (!$result) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg']    = '添加定制赔率失败';
            return $this->jsonData;
        }
        $this->jsonData['msg'] = '添加定制赔率成功';
        return $this->jsonData;
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     *
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     *
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int            $id
     *
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     *
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
