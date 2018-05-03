<?php

namespace app\index\controller;

use app\api\model\AccUri;
use app\cake\model\CakeCustomOdds;
use app\cqssc\model\SscCustomOdds;
use app\egg\model\EggCustomOdds;
use app\pk10\model\Pk10CustomOdds;
use GatewayClient\Gateway;
use think\Controller;
use think\Db;
use think\Log;
use think\Request;

class TestController extends Controller
{

    public function test (&$params)
    {
        echo "\nstart collect\t{$params} open_code\n";
    }

    public function test1 ()
    {
        return $this->fetch();
    }

    public function test2 ()
    {
        $client_ids = Gateway::getClientIdByUid('admin');
        $client_id = $client_ids[0];
        $msg = [
            'type' => 'message',
            'msg' => 'test message2',
        ];
        $msg = json_encode($msg);
        $res = Gateway::sendToClient($client_id, $msg);
        if ($res) {
            return 'send success';
        }
        return 'send fail';
    }

    public function test3 ($tokenint)
    {
        $clients = Gateway::getClientIdByUid($tokenint);
        print_r($clients);
        exit;
    }

    public function auth ()
    {

        $menus = $this->parentMenus();
        $this->assign('menus', $menus);

        return $this->fetch();
    }

    /**
     * @param Request $request
     */
    public function setAuth (Request $request)
    {
        $params = $request->post();
        $accUri = new AccUri();
        $accUri->parent_id = $params['parent_id'];
        $accUri->name = $params['name'];
        $accUri->uri = $params['uri'];
        $accUri->mark = $params['mark'];
        $accUri->save();
        return $this->redirect('index/test/auth');
    }

    public function parentMenus ()
    {
        $menus = $this->getSubMenus();
        return $menus;
    }

    /**
     * @param int $parent_id
     *
     * @return array|bool
     * @throws \think\exception\DbException
     */
    private function getSubMenus ($parent_id = 0)
    {
        $menusArr = [];
        $ids = AccUri::where(['parent_id' => $parent_id])->column('id');
        if (empty($ids)) {
            return false;
        }
        foreach ($ids as $id) {
            $menu = AccUri::get($id);
            $menuArr['id'] = $menu->id;
            $menuArr['parent_id'] = $menu->parent_id;
            $menuArr['name'] = $menu->id . "\t" . $menu->name . '（' . $menu->mark . '）';
            $menuArr['uri'] = $menu->uri;
            $menuArr['mark'] = $menu->mark;
            array_push($menusArr, $menuArr);
            $new_menus = $this->getSubMenus($id);
            if ($new_menus) {
                foreach ($new_menus as $new_menu) {
                    $new_menuArr['id'] = $new_menu['id'];
                    $new_menuArr['parent_id'] = $new_menu['parent_id'];
                    $new_menuArr['name'] = $menu->id . "\t" . "&emsp;&emsp;|— " . $new_menu['name'];
                    $new_menuArr['uri'] = $new_menu['uri'];
                    $new_menuArr['mark'] = $new_menu['mark'];
                    array_push($menusArr, $new_menuArr);
                }
            }
        }
        return $menusArr;
    }


    public function host ()
    {
        return \request()->host();
    }

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeOdds()
    {
        $a = ['sub_pk10' => Pk10CustomOdds::class, 'sub_pcegg' => EggCustomOdds::class, 'sub_cqssc' => SscCustomOdds::class, 'sub_cake' => CakeCustomOdds::class];
        foreach ($a as $type => $model) {
            $connection
                = [
                // 数据库类型
                'type'     => 'mysql',
                // 数据库连接DSN配置
                'dsn'      => '',
                // 服务器地址
                'hostname' => '127.0.0.1',
                // 数据库名
                'database' => $type,
                // 用户名
                'username' => 'root',
                // 密码
                'password' => 'root',
                // 数据库连接端口
                'hostport' => '3306',
                // 数据库连接参数
                'params'   => [],
                // 数据库编码默认采用utf8
                'charset'  => 'utf8',
                // 数据库表前缀
                'prefix'   => '',
            ];
            $b = $model::all();
            foreach ($b as $item) {
                $table = $item->base_odds;
                $res = Db::connect($connection)->table($table)->select();
                foreach ($res as &$value) {
                    $value['bet_limit'] = json_decode($value['bet_limit']);
                }
                $odds = json_encode($res);
                $item->odds = $odds;
                $result = $item->isUpdate()->save();
                if ($result) {
                    echo "成功<br>";
                } else {
                    echo "失败<br>";
                }
            }
        }
    }

    public function decOdds()
    {
        $arr = [
            0 => [
                'limit' => 1000,
                'dec_odds' => 0.100,
            ],
            1 => [
                'limit' => 2000,
                'dec_odds' => 0.200,
            ],
        ];
        return json($arr);
    }
}
