<?php

namespace app\cake\model;


use app\api\model\AccUsers;

class CakeUser extends Base
{
    public function user()
    {
        return $this->belongsTo('app\api\model\AccUsers', 'tokenint')->field('tokenint,tokenext,tokensup,pwd_1,pwd_2', true);
    }

    /**
     * 用户注额列表
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getAll()
    {
        return self::all(function($query) {
            $query->field('tokenint', true);
        });
    }

    /**
     * 查找用户注额详情
     *
     * @param $tokenint
     *
     * @return bool|null|static
     * @throws \think\exception\DbException
     */
    public static function getByUser($tokenint)
    {
        $rec = self::get(function($query) use ($tokenint) {
            $query->field('tokenint', true)->where('tokenint', '=', $tokenint);
        });
        if (is_null($rec)) {
            return false;
        }
        return $rec;
    }

    /**
     * 查找指定注额详情
     *
     * @param $id
     *
     * @return bool|null|static
     * @throws \think\exception\DbException
     */
    public static function getById($id)
    {
        $rec = self::get(function($query) use ($id) {
            $query->field('tokenint', true)->where('id', '=', $id);
        });
        if (is_null($rec)) {
            return false;
        }
        return $rec;
    }

    /**
     * 添加一条用户注额
     *
     * @param $post
     *
     * @return bool|int|string
     */
    public static function addRecord($post)
    {
        $tokenint = AccUsers::getTokenint($post['user_id']);
        $data['tokenint'] = $tokenint;
        $data['money_min'] = floatval(trim($post['money_min']));
        $data['money_max'] = floatval(trim($post['money_max']));
        $data['money_win'] = floatval(trim($post['money_win']));
        $data['trad_rate'] = floatval(trim($post['trad_rate']));
        $data['trad_win'] = floatval(trim($post['trad_win']));
        $data['trad_max'] = floatval(trim($post['trad_max']));
        $data['trad_url'] = trim($post['trad_url']);
        $data['trad_tokensup'] = floatval(trim($post['trad_tokensup']));

        $id = self::insertGetId($data);
        if ($id > 0) {
            return $id;
        }
        return false;
    }

    /**
     * 用户注册成功后自动添加一条默认注额
     *
     * @param $tokenint
     */
    public static function addDefaultRecord($tokenint)
    {
        $data['tokenint'] = $tokenint;
        $data['money_min'] = 10;
        $data['money_max'] = 10000;
        $data['money_win'] = 50000;
        $data['trad_rate'] = 0.5;
        $data['trad_win'] = 5000;
        $data['trad_max'] = 1000;
        $data['trad_url'] = '';
        $data['trad_tokensup'] = '';
        self::insert($data);
    }

    /**
     * 删除一条用户注额
     *
     * @param $id
     *
     * @return bool
     */
    public static function deleteRecord($id)
    {
        $res = self::destroy($id);
        if ($res) {
            return true;
        }
        return false;
    }

    /**
     * 查找记录是否存在
     *
     * @param $id
     *
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function idExist($id)
    {
        $res = self::get($id);
        if (is_null($res)) {
            return false;
        }
        return true;
    }

    /**
     * 修改一条记录
     *
     * @param $id
     * @param $put
     *
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function updateRecord($id, $put)
    {
        $data = [];
        if (isset($put['money_min'])) {
            $data['money_min'] = $put['money_min'];
        }
        if (isset($put['money_max'])) {
            $data['money_max'] = $put['money_max'];
        }
        if (isset($put['money_win'])) {
            $data['money_win'] = $put['money_win'];
        }
        if (isset($put['trad_rate'])) {
            $data['trad_rate'] = $put['trad_rate'];
        }
        if (isset($put['trad_win'])) {
            $data['trad_win'] = $put['trad_win'];
        }
        if (isset($put['trad_max'])) {
            $data['trad_max'] = $put['trad_max'];
        }
        if (isset($put['trad_url'])) {
            $data['trad_url'] = $put['trad_url'];
        }
        if (isset($put['trad_tokensup'])) {
            $data['trad_tokensup'] = $put['trad_tokensup'];
        }
        if (empty($data)) {
            return false;
        }
        $record = self::get($id);
        $res = $record->isUpdate()->save($data);
        if ($res) {
            return true;
        }
        return false;
    }

    /**
     * 重复性检查
     *
     * @param AccUsers $user
     *
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function checkExists(AccUsers $user)
    {
        $res = self::get(['tokenint' => $user->tokenint]);
        if (is_null($res)) {
            return false;
        }
        return true;
    }

    /**
     * 添加一条用户注额
     *
     * @param $money
     * @param $tokenint
     *
     * @throws \think\exception\DbException
     */
    public static function addLtyUser($money, $tokenint)
    {
        $data['tokenint'] = $tokenint;
        $data['money_min'] = $money['money_min'];
        $data['money_max'] = $money['money_max'];
        $data['money_win'] = $money['money_win'];

        $user = AccUsers::getUserByTokenint($tokenint);
        if (!self::checkExists($user)) {
            self::insert($data);
        }

    }

    /**
     * @param       $page
     * @param       $per_page
     * @param array $where
     *
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\exception\DbException
     */
    public static function getList($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;
        $list = self::all(function($query) use ($start, $per_page, $where) {
            $query->where($where)->order('id', 'desc')->limit($start, $per_page);
        });
        return $list;
    }

    /**
     * 删除用户自定义赔率和返水数据
     * @param $tokenint
     *
     * @return int
     */
    public static function delOneByUser($tokenint)
    {
        return self::where(['tokenint' => $tokenint])->delete();
    }
}
