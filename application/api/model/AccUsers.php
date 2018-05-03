<?php

namespace app\api\model;

use think\Model;

class AccUsers extends Model
{
    /*************  关联 开始   ************/

    /****** 盘口  START *********/
    public function sscRatelist()
    {
        return $this->hasMany('app\cqssc\model\SscRatelist', 'tokenint', 'tokenint');
    }

    public function eggRatelist()
    {
        return $this->hasMany('app\egg\model\EggRatelist', 'tokenint', 'tokenint');
    }

    public function cakeRatelist()
    {
        return $this->hasMany('app\cake\model\CakeRatelist', 'tokenint', 'tokenint');
    }

    public function pk10Ratelist()
    {
        return $this->hasMany('app\pk10\model\Pk10Ratelist', 'tokenint', 'tokenint');
    }

    /****** 盘口   END  *********/

    public function money()
    {
        return $this->hasOne('AccMoney', 'tokenint', 'tokenint');
    }


    /*************  关联 结束   ************/


    /**
     * 管理员添加用户
     * @param $user
     * @return int|string
     */
    public static function addMember($user)
    {
        unset($user['cash_money']);
        unset($user['credit_money']);
        $user['tokenint'] = gen_token($user['username'], 'token_int');
        $user['pwd_1'] = password_hash($user['pwd_1'], PASSWORD_BCRYPT);
        $user['pwd_2'] = password_hash($user['pwd_2'], PASSWORD_BCRYPT);
        $user['ctime'] = date('Y-m-d H:i:s');

        return self::insertGetId($user);
    }

    /**
     * 用户注册
     * @param $user
     * @return int|string 返回用户id
     */
    public static function doRegister($user)
    {
        $user['tokenint'] = gen_token($user['username'], 'token_int');
        $user['tokensup'] = gen_token($user['username'], 'token_sup');
        $user['pwd_1'] = password_hash($user['pwd_1'], PASSWORD_BCRYPT);
        $user['pwd_2'] = password_hash($user['pwd_2'], PASSWORD_BCRYPT);
        $user['ctime'] = date('Y-m-d H:i:s');
        $user['type'] = 0;

        return self::insertGetId($user);
    }

    /**
     * 获取一个用户
     * @param $user_id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getMember($user_id)
    {
        return self::field('id, username, nickname, ctime, admin, manager, agent, type, status, tokenint')->find($user_id);
    }

    /**
     * 获取用户列表
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getMembers()
    {
        return self::field('id, username, nickname, ctime, admin, manager, agent, type, status')->select();
    }

    /**
     * @param array $where
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function getUser($where = [])
    {
        return self::get(function ($query) use ($where) {
            $query->field('tokenint, tokenext, tokensup, salt, pwd_1, pwd_2', true)
                ->where($where);
        });
    }

    /**
     * @param array $where
     * @param array $whereOr
     *
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getUsers($where = [], $whereOr = [])
    {
        return self::all(function ($query) use ($where, $whereOr) {
            $query->field('tokenint, tokenext, tokensup, salt, pwd_1, pwd_2', true)
                ->where($where)->whereOr($whereOr);
        });
    }

    /**
     * 修改用户资料
     * @param $user_id
     * @param $upd
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function updateMember($user_id, $upd)
    {
        $user = self::get($user_id);
        $user->nickname = $upd['nickname'];
        $res = $user->save();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 修改用户资料
     * @param AccUsers $user
     * @param $upd
     * @return bool
     */
    public static function adminUpdateMember($user, $upd)
    {
        if (isset($upd['type'])) {
            $user->type = $upd['type'];
        }
        if (isset($upd['nickname'])) {
            $user->nickname = $upd['nickname'];
        }

        $res = $user->isUpdate()->save();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取用户内部密钥
     * @param $user_id
     * @return mixed
     */
    public static function getTokenint($user_id)
    {
        return self::where(['id' => $user_id])->value('tokenint');
    }

    /**
     * @param $tokensup
     *
     * @return mixed
     */
    public static function getTokenintByTokensup($tokensup)
    {
        return self::where(['tokensup' => $tokensup])->value('tokenint');
    }

    /**
     * @param $tokensup
     *
     * @return mixed
     */
    public static function getUseridByTokensup($tokensup)
    {
        return self::where(['tokensup' => $tokensup])->value('id');
    }

    /**
     * 根据超级密钥查找用户
     * @param $tokensup
     *
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserByTokensup($tokensup)
    {
        return self::where(['tokensup' => $tokensup])->find();
    }

    /**
     * 查找所有用户名
     * @return array
     */
    public static function getAllUsername()
    {
        return self::column('username');
    }

    /**
     * @param $username
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserByName($username)
    {
        $user = self::where(['username' => $username])->find();
        if (is_null($user)) {
            return false;
        }
        return $user;
    }

    /**
     * @param $tokenint
     * @return bool|null|static
     * @throws \think\exception\DbException
     */
    public static function getUserByTokenint($tokenint)
    {
        $user = self::get(function ($query) use ($tokenint) {
            $query->where(['tokenint' => $tokenint]);
        });
        if (is_null($user)) {
            return false;
        }
        return $user;
    }

    /**
     * 查找用户id
     * @param $tokenint
     * @return mixed
     */
    public static function getUserIdByTokenint($tokenint)
    {
        return self::where(['tokenint' => $tokenint])->value('id');
    }

    /**
     * 修改登录密码
     * @param $user
     * @param $newPwd
     * @return bool
     */
    public static function updatePwd1($user, $newPwd)
    {
        $user->pwd_1 = password_hash($newPwd, PASSWORD_BCRYPT);
        $res = $user->save();
        if ($res) {
            return true;
        }
        return false;
    }

    /**
     * 修改支付密码
     * @param $user
     * @param $newPwd
     * @return bool
     */
    public static function updatePwd2($user, $newPwd)
    {
        $user->pwd_2 = password_hash($newPwd, PASSWORD_BCRYPT);
        $res = $user->save();
        if ($res) {
            return true;
        }
        return false;
    }

    /**
     * 禁用用户
     * @param $id
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function bandUser($id)
    {
        $user = self::get($id);
        $user->status = 0;
        $result = $user->save();
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * 禁用用户
     * @param $id
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function unBandUser($id)
    {
        $user = self::get($id);
        $user->status = 1;
        $result = $user->save();
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * @param $user_id
     * @param $status
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function updateUserStatus($user_id, $status)
    {
        $user = self::get($user_id);
        $user->status = $status;
        $result = $user->save();
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * 检查用户是否存在
     * @param $id
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function userExist($id)
    {
        $user = self::get($id);
        if (is_null($user)) {
            return false;
        }
        return true;
    }


    /**
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\exception\DbException
     */
    public static function getSscRatelist($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;

        $list = self::field('id as user_id, username, nickname, tokenint')
            ->with(['sscRatelist' => function($query) {
                $query->order('ratewin_name');
            }])
            ->where($where)
            ->order('id', 'desc')
            ->limit($start, $per_page)
            ->select();

        return $list;
    }

    /**
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\exception\DbException
     */
    public static function getEggRatelist($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;

        $list = self::field('id as user_id, username, nickname, tokenint')
            ->with(['eggRatelist' => function($query) {
                $query->order('ratewin_name');
            }])
            ->where($where)
            ->order('id', 'desc')
            ->limit($start, $per_page)
            ->select();

        return $list;
    }

    /**
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\exception\DbException
     */
    public static function getCakeRatelist($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;

        $list = self::field('id as user_id, username, nickname, tokenint')
            ->with(['cakeRatelist' => function($query) {
                $query->order('ratewin_name');
            }])
            ->where($where)
            ->order('id', 'desc')
            ->limit($start, $per_page)
            ->select();

        return $list;
    }

    /**
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\exception\DbException
     */
    public static function getPk10Ratelist($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;

        $list = self::field('id as user_id, username, nickname, tokenint')
            ->with(['pk10Ratelist' => function($query) {
                $query->order('ratewin_name');
            }])
            ->where($where)
            ->order('id', 'desc')
            ->limit($start, $per_page)
            ->select();

        return $list;
    }

    /**
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\exception\DbException
     */
    public static function getList($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;

        $list = self::field('id as user_id, username, nickname, tokenint, ctime, admin, manager, agent, type, status')
            ->with(['money'])
            ->where($where)
            ->order('id', 'desc')
            ->limit($start, $per_page)
            ->select();

        return $list;
    }

    /**
     * 查找用户内部密钥列表
     * @param $where
     * @return array
     */
    public static function getTokenints($where)
    {
        return self::where($where)->column('tokenint');
    }
}
