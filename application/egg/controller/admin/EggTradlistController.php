<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/13
 * Time: 14:21
 */

namespace app\egg\controller\admin;


use app\auth\controller\AdminBaseController;
use app\egg\model\EggTradlist;
use think\Request;

class EggTradlistController extends AdminBaseController
{
    /**
     * 转盘列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list = EggTradlist::all();
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

        $post['trad_url'] = reset_domain($post['trad_url']) . "/egg/order2";
        $trad = new EggTradlist();
        $trad_id = $trad->insertGetId($post);
        if (!$trad_id) {
            return [
                'status' => 0,
                'msg'    => '添加转盘失败'
            ];
        }

        $theTrad = EggTradlist::get($trad_id);
        update_trad($theTrad, 'egg');
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

        if (EggTradlist::checkDuplicate(trim($post['trad_tokensup']), trim($post['trad_name']))) {
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
        $trad = EggTradlist::get($id);
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
        if (isset($put['trad_tokensup']) && isset($put['trad_name']) && EggTradlist::checkDuplicate(trim($put['trad_tokensup']), trim($put['trad_name']))) {
            return [
                'status' => 0,
                'msg'    => '修改失败，重复的名称或密钥'
            ];
        }
        $result = EggTradlist::updTrad($put, $id);
        if (!$result) {
            return [
                'status' => 0,
                'msg'    => '修改失败'
            ];
        }
        $trad = EggTradlist::get($id);
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
        if (!EggTradlist::isExist($id)) {
            return [
                'status' => 404,
                'msg'    => '记录不存在'
            ];
        }
        $result = EggTradlist::destroy($id);
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