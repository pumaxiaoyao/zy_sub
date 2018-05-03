<?php

namespace app\cake\model;

use think\Model;

class Base extends Model
{
    protected $connection = [
        // 数据库类型
        'type'     => 'mysql',
        // 数据库连接DSN配置
        'dsn'      => '',
        // 服务器地址
        'hostname' => '127.0.0.1',
        // 数据库名
        'database' => 'sub_cake',
        // 数据库用户名
        'username' => 'root',
        // 数据库密码
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

    /**
     * @param $tableName
     *
     * @return bool
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public static function tableExists($tableName)
    {
        $res = self::execute('show tables like "' . $tableName . '"');
        if ($res > 0) {
            return true;
        }
        return false;
    }

}
