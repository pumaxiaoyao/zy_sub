<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/5
 * Time: 11:32
 */

namespace app\cake\controller\admin;


use app\auth\controller\AdminBaseController;
use app\cake\model\Base;
use app\cake\model\CakeCustomOdds;
use app\cake\model\CakeOdds;
use app\cake\model\CakeRatelist;
use think\Request;

class CakeOddsController extends AdminBaseController
{
    /**
     * 赔率列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $get = request()->only('user_id', 'get');
        $user_id = isset($get['user_id']) ? trim($get['user_id']) : null;
        $exists_names = exists_cake_odds_table();
        if (!is_null($user_id)) {
            $tokenint = get_user_tokenint_by_id($user_id);
            $ratelist = CakeRatelist::getListByUser($tokenint)->toArray();
            $exists_names = array_column($ratelist, 'ratewin_set');
        }
        $oddsArr = [];
        foreach ($exists_names as $key => $exists_name) {
            $arr = explode('_', $exists_name);
            $letter = $arr[1];
            $odds = CakeOdds::getAll($exists_name);
            $oddsArr[$key]['name'] = $letter;
            $oddsArr[$key]['table'] = $exists_name;
            $oddsArr[$key]['odds'] = $odds;
            if (isset($tokenint)) {
                $oddsArr[$key]['custom_odds'] = CakeCustomOdds::getOddsByTableAndUser($exists_name, $tokenint);
            }
        }
        $this->jsonData['data']['odds_list'] = $oddsArr;
        return $this->jsonData;
    }

    /**
     * 新建赔率表
     *
     * @param Request $request
     *
     * @return array
     * @throws \think\db\exception\BindParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function save(Request $request)
    {
        $table_names = odds_table_names('cake_');
        $exists_names = exists_cake_odds_table();
        $newTable = array_diff($table_names, $exists_names);
        $newTableName = array_shift($newTable);
        $arr = explode('_', $newTableName);
        $letter = $arr[1];
        $call_add_cake_odds_procudure = 'call add_cake_odds(:type)';
        $call_add_cake_odds_procudure_params = [
            'type' => $letter
        ];
        Base::query($call_add_cake_odds_procudure, $call_add_cake_odds_procudure_params);
        $exists = Base::execute('show tables like "' . $newTableName . '"');
        if ($exists) {
            $odds = CakeOdds::name($newTableName)->select();
            return [
                'status' => 200,
                'msg' => '创建赔率表[ ' . $newTableName . ' ]成功',
                'data' => [
                    'odds' => $odds
                ]
            ];
        }
        return [
            'status' => 0,
            'msg' => '创建赔率表[ ' . $newTableName . ' ]失败'
        ];
    }

    /**
     * 查看指定赔率表数据
     *
     * @param $id
     *
     * @return array|false|static[]
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $tableName = $id;
        $customOdds = null;
        $get = request()->only('user_id', 'get');
        $user_id = isset($get['user_id']) ? trim($get['user_id']) : null;
        if (!is_null($user_id)) {
            $tokenint = get_user_tokenint_by_id($user_id);
            $customOdds = CakeCustomOdds::getOddsByTableAndUser($tableName, $tokenint);
        }
        $exists = Base::tableExists($tableName);
        if (!$exists) {
            return [
                'status' => 404,
                'msg' => '赔率表[ ' . $tableName . ' ]不存在'
            ];
        }
        $odds = CakeOdds::getAll($tableName);
        return [
            'status' => 200,
            'msg' => 'success',
            'data' => [
                'odds' => $odds,
                'custom_odds' => $customOdds,
            ]
        ];
    }

    /**
     * 查看指定赔率表数据
     *
     * @param $id
     *
     * @return array|false|static[]
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $tableName = $id;
        $exists = Base::tableExists($tableName);
        if (!$exists) {
            return [
                'status' => 404,
                'msg' => '赔率表[ ' . $tableName . ' ]不存在'
            ];
        }
        $odds = CakeOdds::getAll($tableName);
        return [
            'status' => 200,
            'msg' => 'success',
            'data' => [
                'odds' => $odds
            ]
        ];
    }

    /**
     * 修改赔率
     *
     * @param Request $request
     * @param         $id
     *
     * @return array
     * @throws \Exception
     */
    public function update(Request $request, $id)
    {
        $tableName = $id;
        $put = $request->put();
        $odds = $put['odds'];
        if (!Base::tableExists($tableName)) {
            return [
                'status' => 404,
                'msg' => '该赔率表不存在'
            ];
        }
        $oddsModel = new CakeOdds();
        $oddsModel->setTable($tableName);

        $res = $oddsModel->isUpdate()->saveAll($odds);
        if (!$res) {
            return [
                'status' => 0,
                'msg' => '修改赔率失败'
            ];
        }
        return [
            'status' => 201,
            'msg' => '修改赔率成功',
            'data' => [
                'odds' => $res
            ]
        ];
    }

    /**
     * 删除赔率表
     *
     * @param $id
     *
     * @return array
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function delete($id)
    {
        $tableName = trim($id);
        if (!Base::tableExists($tableName)) {
            return [
                'status' => 404,
                'msg' => '该赔率表不存在'
            ];
        }

        $exist_tables = exists_cake_odds_table();
        if (1 >= count($exist_tables)) {
            return [
                'status' => 0,
                'msg' => '删除失败，必须保留至少一个赔率表'
            ];
        }

        $call_del_odds_procudure = 'call del_odds(:tb_name)';
        $call_del_odds_procudure_params = [
            'tb_name' => $tableName
        ];
        Base::query($call_del_odds_procudure, $call_del_odds_procudure_params);
        if (!Base::tableExists($tableName)) {
            CakeRatelist::where(['ratewin_set' => $tableName])->delete();
            return [
                'status' => 200,
                'msg' => '删除赔率表[ ' . $tableName . ' ]成功'
            ];
        }
        return [
            'status' => 0,
            'msg' => '删除赔率表[ ' . $tableName . ' ]失败'
        ];
    }
}