<?php

namespace app\cake\model;


class CakeTradlist extends Base
{

    /**
     * 检查重复
     *
     * @param $trad_tokensup
     * @param $trad_name
     *
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function checkDuplicate($trad_tokensup, $trad_name)
    {
        $record = self::get(function($query) use ($trad_tokensup, $trad_name) {
            $query->where([
                'trad_tokensup' => $trad_tokensup
            ])->whereOr([
                'trad_name' => $trad_name
            ]);
        });
        if (is_null($record)) {
            return false;
        }
        return true;
    }

    /**
     * 修改转盘设置
     *
     * @param $put
     * @param $id
     *
     * @return bool
     * @throws \think\exception\PDOException
     */
    public static function updTrad($put, $id)
    {
        self::startTrans();
        try {
            $trad = self::get($id);
            $result = $trad->isUpdate()->save($put);
            if (!$result) {
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


}
