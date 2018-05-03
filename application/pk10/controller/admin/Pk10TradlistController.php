<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/13
 * Time: 14:21
 */

namespace app\pk10\controller\admin;


use app\auth\controller\AdminBaseController;
use app\pk10\model\Pk10Tradlist;
use think\Request;

class Pk10TradlistController extends AdminBaseController
{
    /**
     * 转盘列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list = Pk10Tradlist::all();
        return [
            'status' => 200,
            'msg'    => 'success',
            'data'   => [
                'trad_list' => $list
            ]
        ];
    }

    /**
     * 添加母盘盘口
     *
     * @param Request $request
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $post = $request->only([
            'trad_name',
            'trad_tokensup',
            'trad_url'
        ], 'post');
        $this->checkPost($post);

        $post['trad_url'] = reset_domain($post['trad_url']) . "/pk10/order2";
        $trad = new Pk10Tradlist();
        $trad_id = $trad->insertGetId($post);
        if (!$trad_id) {
            return [
                'status' => 0,
                'msg'    => '添加转盘失败'
            ];
        }

        $theTrad = Pk10Tradlist::get($trad_id);
        update_trad($theTrad, 'pk10');
        return [
            'status' => 200,
            'msg'    => '添加转盘成功',
            'data'   => [
                'trad' => $theTrad
            ]
        ];
    }

    /**
     * 检查post参数有效性
     *
     * @param $post
     *
     * @throws \think\exception\DbException
     */
    private function checkPost($post)
    {
        if (!isset($post['trad_name']) || '' == trim($post['trad_name'])) {
            exit(json_encode([
                'status' => 0,
                'msg'    => '转盘名称不能为空'
            ]));
        }
        if (!isset($post['trad_tokensup']) || '' == trim($post['trad_tokensup'])) {
            exit(json_encode([
                'status' => 0,
                'msg'    => '转盘密钥不能为空'
            ]));
        }
        if (!isset($post['trad_url']) || '' == trim($post['trad_url'])) {
            exit(json_encode([
                'status' => 0,
                'msg'    => '转盘接口地址不能为空'
            ]));
        }

        if (Pk10Tradlist::checkDuplicate(trim($post['trad_tokensup']), trim($post['trad_name']))) {
            exit(json_encode([
                'status' => 0,
                'msg'    => '请匆重复添加'
            ]));
        }
    }

    /**
     * 编辑
     *
     * @param $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $trad = Pk10Tradlist::get($id);
        if (!$trad) {
            return [
                'status' => 404,
                'msg'    => '该记录不存在'
            ];
        }
        return [
            'status' => 200,
            'msg'    => 'success',
            'data'   => [
                'trad' => $trad
            ]
        ];
    }

    /**
     * 修改
     *
     * @param Request $request
     * @param         $id
     *
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function update(Request $request, $id)
    {
        $put = $request->put();
        if (empty($put)) {
            return [
                'status' => 0,
                'msg'    => '修改失败，参数不能为空'
            ];
        }
        if (isset($put['trad_tokensup']) && isset($put['trad_name']) && Pk10Tradlist::checkDuplicate(trim($put['trad_tokensup']), trim($put['trad_name']))) {
            return [
                'status' => 0,
                'msg'    => '修改失败，重复的名称或密钥'
            ];
        }
        $result = Pk10Tradlist::updTrad($put, $id);
        if (!$result) {
            return [
                'status' => 0,
                'msg'    => '修改失败'
            ];
        }
        $trad = Pk10Tradlist::get($id);
        return [
            'status' => 200,
            'msg'    => '修改成功',
            'data'   => [
                'trad' => $trad
            ]
        ];
    }

    /**
     * 删除一条记录
     *
     * @param $id
     *
     * @return array
     * @throws \think\exception\DbException
     */
    public function delete($id)
    {
        if (!Pk10Tradlist::isExist($id)) {
            return [
                'status' => 404,
                'msg'    => '记录不存在'
            ];
        }
        $result = Pk10Tradlist::destroy($id);
        if ($result) {
            return [
                'status' => 200,
                'msg'    => '删除记录成功'
            ];
        }
        return [
            'status' => 0,
            'msg'    => '删除记录失败'
        ];
    }
}