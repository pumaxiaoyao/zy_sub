<?php

namespace app\api\model;

use think\Model;

class AccArticle extends Model
{
    /**
     * @param $page
     * @param $per_page
     * @param array $where
     * @return false|static[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getArticles($page, $per_page, $where = [])
    {
        $start = ($page - 1) * $per_page;
        $list = self::field('tokenint, content', true)
            ->where($where)
            ->order('id', 'desc')
            ->limit($start, $per_page)
            ->select();

        return $list;
    }

    /**
     * 检查指定文章是否存在
     * @param $id
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function isExists($id)
    {
        $result = self::get($id);
        if (is_null($result)) {
            return false;
        }
        return true;
    }

}
