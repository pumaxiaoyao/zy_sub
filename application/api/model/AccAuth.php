<?php

namespace app\api\model;

use think\Model;

class AccAuth extends Model
{
    /**
     * @param $tokenint
     * @return array
     */
    public static function getCheckedAuth($tokenint)
    {
        return self::where(['tokenint' => $tokenint])->column('uri_id');
    }

    /**
     * 删除权限
     * @param $tokenint
     * @param $delAuthIds
     * @return bool
     */
    public static function deleteAuth($tokenint, $delAuthIds)
    {
        $ids = self::where(['tokenint' => $tokenint, 'uri_id' => ['in', $delAuthIds]])->column('id');
        $result = self::destroy($ids);
        if ($result > 0) {
            return true;
        }
        return false;
    }

    /**
     * 添加权限
     * @param $user_id
     * @param $addAuthIds
     * @return bool
     */
    public static function addAuth($user_id, $addAuthIds)
    {
        $data = [];
        $tokenint = get_user_tokenint_by_id($user_id);
        foreach ($addAuthIds as $key => $authId) {
            $item['tokenint'] = $tokenint;
            $item['user_id'] = $user_id;
            $item['uri_id'] = $authId;
            $item['uri'] = AccUri::getUri($authId);
            array_push($data, $item);
        }
        $result = self::insertAll($data);
        if ($result > 0) {
            return true;
        }
        return false;
    }

    /**
     * 删除用户权限
     * @param $tokenint
     *
     * @return int
     */
    public static function delAuth($tokenint)
    {
        return self::where(['tokenint' => $tokenint])->delete();
    }

    /**
     * 检查权限
     * @param $tokenint
     * @param $uri
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function hasAuth($tokenint, $uri)
    {
        $result = self::where(['tokenint' => $tokenint, 'uri' => $uri])->find();
        if (is_null($result)) {
            return false;
        }
        return true;
    }
}
