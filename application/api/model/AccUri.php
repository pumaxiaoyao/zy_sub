<?php

namespace app\api\model;

use think\Model;

class AccUri extends Model
{
    /**
     * @param $id
     * @return mixed
     */
    public static function getUri($id)
    {
        return self::where(['id' => $id])->value('uri');
    }

    /**
     * @param $uri
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOneByUri($uri)
    {
        return self::where(['uri' => $uri])->find();
    }
}
