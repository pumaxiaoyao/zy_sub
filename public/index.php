<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

$domain = 'http://lty-main.com';
$headers = apache_request_headers();
$origin = isset($headers['Origin']) ? $headers['Origin'] : null;
$origins = ['http://lty-sub.com', 'http://shady.com:8080', 'http://shady.com:8090', 'http://fish-blog.com:8080'];
if (in_array($origin, $origins)) {
    $domain = $origin;
}

header('Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Origin:' . $domain);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE');

if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    exit;
}

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';


//\think\Build::module('admin');
