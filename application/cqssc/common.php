<?php

use app\cqssc\model\Base;


/**
 * 数据库中存在的赔率表
 * @return array
 * @throws \think\db\exception\BindParamException
 * @throws \think\exception\PDOException
 */
function exists_ssc_odds_table()
{
    $table_names  = odds_table_names('ssc_');
    $exists_names = [];
    foreach ($table_names as $key => $table_name) {
        $res = Base::execute('show tables like "' . $table_name . '"');
        if ($res > 0) {
            array_push($exists_names, $table_name);
        }
    }
    return $exists_names;
}