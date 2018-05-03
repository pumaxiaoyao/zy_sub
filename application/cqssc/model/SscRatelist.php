<?php

namespace app\cqssc\model;


use app\api\model\AccUsers;

class SscRatelist extends Base
{
    public function user()
    {
        return $this->belongsTo('app\api\model\AccUsers', 'tokenint')->field('tokenint,tokenext,tokensup,pwd_1,pwd_2', true);
    }

    /**
     * 所有用户可选盘
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getAll()
    {
        return self::all(function($query) {
            $query->field('tokenint', true)->order('id', 'desc');
        });
    }

    /**
     * 指定用户可选盘
     *
     * @param $tokenint
     *
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public static function getListByUser($tokenint)
    {
        return self::all(function($query) use ($tokenint) {
            $query->field('tokenint', true)->where('tokenint', '=', $tokenint)->order('ratewin_name', 'asc');
        });
    }

    /**
     * 查找指定用户盘口
     *
     * @param $id
     *
     * @return bool|null|static
     * @throws \think\exception\DbException
     */
    public static function getRateById($id)
    {
        $rate = self::get(function($query) use ($id) {
            $query->field('tokenint', true)->where(['id' => $id]);
        });
        if (is_null($rate)) {
            return false;
        }
        return $rate;
    }

    /**
     * 删除用户盘口
     *
     * @param $id
     *
     * @return int
     */
    public static function deleteById($id)
    {
        return self::destroy($id);
    }

    /**
     * 记录是否存在
     *
     * @param $id
     *
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function isExist($id)
    {
        $rate = self::get($id);
        if (is_null($rate)) {
            return false;
        }
        return true;
    }

    /**
     * 添加用户盘口
     *
     * @param $post
     *
     * @return bool|int|string
     */
    public static function addRate($post)
    {
        $tokenint             = AccUsers::getTokenint($post['user_id']);
        $ratewin_set          = 'ssc_' . strtolower(trim($post['ratewin_name']));
        $rate['tokenint']     = $tokenint;
        $rate['ratewin_name'] = strtoupper($post['ratewin_name']);
        $rate['ratewin_set']  = $ratewin_set;
        $rate['sel']          = $post['sel'];
        $rate_id              = self::insertGetId($rate);
        if (!$rate_id) {
            return false;
        }
        return $rate_id;
    }

    /**
     * 检查是否重复
     *
     * @param $post
     *
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function checkRateDuplicate($post)
    {
        $tokenint     = AccUsers::getTokenint($post['user_id']);
        $ratewin_name = strtoupper(trim($post['ratewin_name']));
        $ratewin_set  = 'ssc_' . strtolower($ratewin_name);
        $rate         = self::get(function($query) use ($tokenint, $ratewin_name, $ratewin_set) {
            $query->where([
                'tokenint'     => $tokenint,
                'ratewin_name' => $ratewin_name,
                'ratewin_set'  => $ratewin_set
            ]);
        });
        if (is_null($rate)) {
            return false;
        }
        return true;
    }

    /**
     * 更新用户盘口
     *
     * @param $put
     * @param $id
     *
     * @return bool
     * @throws \think\exception\PDOException
     */
    public static function updateRate($put, $id)
    {
        self::startTrans();
        try {
            $ratewin_name = trim($put['ratewin_name']);
            $ratewin_set  = 'ssc_' . strtolower($ratewin_name);

            if (1 == $put['sel']) {
                // 如果修改默认，则将原默认设置为非默认
                $tokenint          = self::getTokenintById($id);
                $rate_default      = self::get([
                    'sel'      => $put['sel'],
                    'tokenint' => $tokenint
                ]);
                $rate_default->sel = 0;
                $rate_default->isUpdate()->save();
            }

            $rate               = self::get($id);
            $rate->ratewin_name = strtoupper($ratewin_name);
            $rate->ratewin_set  = $ratewin_set;
            $rate->sel          = $put['sel'];

            $res = $rate->isUpdate()->save();
            if (!$res) {
                self::rollback();
                return false;
            }
            self::commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return false;
        }
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public static function getTokenintById($id)
    {
        return self::where(['id' => $id])->value('tokenint');
    }

    /**
     * 获取用户专属盘
     *
     * @param $tokenint
     *
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public static function getPanByUser($tokenint)
    {
        $exists_tables = exists_ssc_odds_table();
        $pan           = self::where([
            'tokenint' => $tokenint,
            'sel'      => 1
        ])->find();
        // 如果用户专属盘对应的数据表存在，则直接返回
        if ($pan && in_array($pan['ratewin_set'], $exists_tables)) {
            return $pan['ratewin_name'];
        }
        // 否则，取用户其余备选盘口与数据库中存在的赔率表的交集中的第一个
        $pans      = self::where([
            'tokenint' => $tokenint,
            'sel'      => 0
        ])->column('ratewin_set');
        $validPans = array_intersect($exists_tables, $pans);
        if (empty($validPans)) {
            // 如果交集为空，则返回数据库的所有赔率表的第一个
            $tableName = $exists_tables[0];
        } else {
            // 如果交集存在，取交集第一条
            $tableName = array_shift($validPans);
        }
        return pan_type($tableName);
    }

    /**
     * 用户注册成功，自动在用户 ratelist 插入一条默认盘口
     *
     * @param $tokenint
     *
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public static function addDefaultRatelist($tokenint)
    {
        $call_add_ratelist_procudure        = 'call add_ratelist(:tokenint)';
        $call_add_ratelist_procudure_params = [
            'tokenint' => $tokenint,
        ];
        Base::execute($call_add_ratelist_procudure, $call_add_ratelist_procudure_params);
    }

    /**
     * 插入用户盘 ABCD
     *
     * @param $tables
     * @param $tokenint
     *
     * @return int|string
     * @throws \think\exception\DbException
     */
    public static function addPans($tables, $tokenint)
    {
        $data = [];
        foreach ($tables as $key => $table) {
            $data[$key]['tokenint']     = $tokenint;
            $data[$key]['ratewin_name'] = pan_type($table);
            $data[$key]['ratewin_set']  = strtolower($table);
            $data[$key]['sel']          = 0;
            $ratewin_name               = $data[$key]['ratewin_name'];
            $ratewin_set                = $data[$key]['ratewin_set'];
            $rate                       = self::get(function($query) use ($tokenint, $ratewin_name, $ratewin_set) {
                $query->where([
                    'tokenint'     => $tokenint,
                    'ratewin_name' => $ratewin_name,
                    'ratewin_set'  => $ratewin_set
                ]);
            });
            if (!is_null($rate)) {
                continue;
            }
        }
        return self::insertAll($data);
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
