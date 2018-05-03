<?php

namespace app\api\controller;

use app\api\model\AccMoney;
use app\api\model\AccMychg;
use app\api\model\AccUsers;
use app\auth\controller\BaseController;
use app\cake\model\CakeRatelist;
use app\cake\model\CakeUser;
use app\cqssc\model\SscRatelist;
use app\cqssc\model\SscUser;
use app\egg\model\EggRatelist;
use app\egg\model\EggUser;
use app\pk10\model\Pk10Ratelist;
use app\pk10\model\Pk10User;
use think\helper\Time;
use think\Request;

class AccUsersController extends BaseController
{

    /**
     * 用户注册
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $postData = $request->only(['username', 'nickname', 'pwd_1', 'pwd_2'], 'post');
        $error = $this->validate($postData, [
            'username|用户名' => 'require|max:25|unique:acc_users',
            'pwd_1|登录密码' => 'require|min:6|max:16',
            'pwd_2|支付密码' => 'require|min:6|max:16',
        ]);
        if (true !== $error) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = $error;
            return $this->jsonData;
        }

        $user_id = AccUsers::doRegister($postData);
        if ($user_id) {
            $tokenint = AccUsers::getTokenint($user_id);
            // 注册成功，添加用户资金记录
            AccMoney::addMoney($user_id);
            // 注册成功，插入一条默认盘口
            SscRatelist::addDefaultRatelist($tokenint);
            Pk10Ratelist::addDefaultRatelist($tokenint);
            EggRatelist::addDefaultRatelist($tokenint);
            CakeRatelist::addDefaultRatelist($tokenint);
            // 注册成功，添加一条用户注额
            SscUser::addDefaultRecord($tokenint);
            Pk10User::addDefaultRecord($tokenint);
            EggUser::addDefaultRecord($tokenint);
            CakeUser::addDefaultRecord($tokenint);
            return ['status' => 201, 'msg' => '注册成功', 'data' => []];
        } else {
            return ['status' => 0, 'msg' => '注册失败，请重试', 'data' => []];
        }
    }

    /**
     * 用户查看本人信息
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read()
    {
        $user = $this->user;
        $where['tokenint'] = ['=', $this->user->tokenint];

        $yk = AccMychg::todayYk($where);
        $fs = AccMychg::todayFs($where);
        // 用户资金
        $money = AccMoney::getMoneyByUserid($user->id);

        // 用户有哪些彩种
        $valid_types = [];
        $sscExists = SscUser::checkExists($user);
        if ($sscExists) {
            array_push($valid_types, 'cqssc');
        }

        $pk10Exists = Pk10User::checkExists($user);
        if ($pk10Exists) {
            array_push($valid_types, 'bjpk10');
        }

        $eggExists = EggUser::checkExists($user);
        if ($eggExists) {
            array_push($valid_types, 'pcegg');
        }

        $cakeExists = CakeUser::checkExists($user);
        if ($cakeExists) {
            array_push($valid_types, 'cakeno');
        }

        if (is_null($user)) {
            $uData = [];
        } else {
            unset_user_fields($user);
            $uData = $user;
            $uData['money'] = $money;
            $uData['yk'] = $yk;
            $uData['fs'] = $fs;
            $uData['valid_types'] = $valid_types;
        }

        $this->jsonData['data']['user'] = $uData;

        return $this->jsonData;
    }


    /**
     * 用户信息修改
     * @param Request $request
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update(Request $request)
    {
        $put = $request->only(['nickname', 'put']);
        $user = AccUsers::get($this->user->id);
        $user->nickname = trim($put['nickname']);
        $result = $user->save();
        $uData = AccUsers::getMember($user->id);
        unset($uData->tokenint);
        if ($result) {
            $this->jsonData['msg'] = '修改用户信息成功';
            $this->jsonData['data'] = $uData;
            return $this->jsonData;
        }
        $this->jsonData['status'] = 0;
        $this->jsonData['data'] = $uData;
        $this->jsonData['msg'] = '修改用户信息失败';
        return $this->jsonData;
    }

    /**
     * 用户注册时的用户名有效性检测接口
     * @param Request $request
     * @return array
     */
    public function checkUsername(Request $request)
    {
        $get = $request->only('username', 'get');
        if (username_is_duplicate(trim($get['username']))) {
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '该用户名已被使用';
            return $this->jsonData;
        }
        $this->jsonData['msg'] = '该用户名可用';
        return $this->jsonData;
    }

    /**
     * 修改用户登录密码
     * @param Request $request
     * @return array
     */
    public function updatePassword(Request $request)
    {
        $put = $request->only(['old_pwd', 'pwd_1'], 'put');
        $user = $this->user;
        if (password_verify($put['old_pwd'], $user->pwd_1)) {
            $res = AccUsers::updatePwd1($user, trim($put['pwd_1']));
            if ($res) {
                $this->jsonData['msg'] = '登录密码修改成功';
                return $this->jsonData;
            }
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '登录密码修改失败';
            return $this->jsonData;
        }
        $this->jsonData['status'] = 0;
        $this->jsonData['msg'] = '原密码有误，请重新输入';
        return $this->jsonData;
    }

    /**
     * 修改用户支付密码
     * @param Request $request
     * @return array
     */
    public function updatePassword2(Request $request)
    {
        $put = $request->only(['old_pwd', 'pwd_2'], 'put');
        $user = $this->user;
        if (password_verify($put['old_pwd'], $user->pwd_2)) {
            $res = AccUsers::updatePwd2($user, trim($put['pwd_2']));
            if ($res) {
                $this->jsonData['msg'] = '支付密码修改成功';
                return $this->jsonData;
            }
            $this->jsonData['status'] = 0;
            $this->jsonData['msg'] = '支付密码修改失败';
            return $this->jsonData;
        }
        $this->jsonData['status'] = 0;
        $this->jsonData['msg'] = '原密码有误，请重新输入';
        return $this->jsonData;
    }
}
