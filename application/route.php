<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;


/**
 * 后台路由
 */

// 后台首页
Route::get('ltyAdmin', 'index/adminIndex/index');

// 首页
Route::get('/', 'index/Index/index');

Route::group('admin', function () {

    // 资金变动
    Route::get('chgs', 'api/admin.AccMychg/index');

    // 结算报表
    Route::get('clearList', 'api/admin.SummaryData/clearList');
    Route::get('details', 'api/admin.SummaryData/detail');

    // 下注统计报表
    Route::get('summary', 'api/admin.SummaryData/index');


    // 上传图片
    Route::post('image', 'api/admin.AccArticles/upload');

    // 权限树
    Route::get('authTree', 'api/admin.AccUri/authTree');
    // 权限无限级分类
    Route::get('auth', 'api/admin.AccUri/getMenusTree');
    // 修改权限
    Route::post('auth', 'api/admin.AccAuth/setAdminAuth');

    // 文章
    Route::resource('articles', 'api/admin.AccArticles', ['except' => ['create', 'edit']]);

    // 绑定client_id 和 当前 用户
    Route::post('client', 'api/admin.GatewayWorker/bindClientId');
    // 解绑client_id 和 当前 用户
    Route::delete('client', 'api/admin.GatewayWorker/unBindClientId');

    // 用户表 相关操作路由
    Route::resource('users', 'api/admin.AccUsers', ['except' => ['create']]);
    Route::put('unbinduser/:id', 'api/admin.AccUsers/unBindUser');
    Route::post('lottery', 'api/admin.AccUsers/setLottery');
    Route::get('lotteryList/:user_id', 'api/admin.AccUsers/lotteryList');
    Route::get('lottery/:user_id/edit', 'api/admin.AccUsers/editLottery');
    Route::post('userLottery/:user_id', 'api/admin.AccUsers/updateUserLottery');

    // 管理员/代理 登录退出相关操作
    Route::post('token', 'api/admin.Token/login');
    Route::delete('token', 'api/admin.Token/logout');

    // 提现表 相关操作路由
    Route::resource('withdraws', 'api/admin.AccWithdraws', ['only' => ['index', 'read', 'delete', 'update']]);

    // 在线用户列表
    Route::get('online', 'api/admin.AccOnline/index');

    // 刷新令牌
    Route::get('refreshToken', 'api/admin.Token/refreshToken');

    // 用户资金列表
    Route::get('money', 'api/admin.AccMoney/index');
    Route::get('refreshMoney', 'api/admin.AccMoney/refreshTradMoney');

    // 用户充值相关
    Route::resource('topups', 'api/admin.AccTopups', ['only' => ['index', 'read', 'delete', 'update']]);

    // 登录信息
    Route::resource('logins', 'api/admin.AccLogin', ['only' => ['index', 'read']]);

    // 用户注额表相关操作路由
    Route::resource('ssc/user', 'cqssc/admin.SscUser', ['except' => ['create', 'read']]);
    Route::resource('pk10/user', 'pk10/admin.Pk10User', ['except' => ['create', 'read']]);
    Route::resource('egg/user', 'egg/admin.EggUser', ['except' => ['create', 'read']]);
    Route::resource('cake/user', 'cake/admin.CakeUser', ['except' => ['create', 'read']]);

    // 用户可选盘口相关
    Route::resource('ssc/ratelist', 'cqssc/admin.SscRatelist', ['except' => ['create']]);
    Route::resource('pk10/ratelist', 'pk10/admin.Pk10Ratelist', ['except' => ['create']]);
    Route::resource('egg/ratelist', 'egg/admin.EggRatelist', ['except' => ['create']]);
    Route::resource('cake/ratelist', 'cake/admin.CakeRatelist', ['except' => ['create']]);

    // 赔率表相关
    Route::resource('ssc/odds', 'cqssc/admin.SscOdds', ['except' => ['create']]);
    Route::resource('pk10/odds', 'pk10/admin.Pk10Odds', ['except' => ['create']]);
    Route::resource('egg/odds', 'egg/admin.EggOdds', ['except' => ['create']]);
    Route::resource('cake/odds', 'cake/admin.CakeOdds', ['except' => ['create']]);

    // 开奖历史
    Route::get('ssc/history/lottery', 'cqssc/admin.SscLottery/history');
    Route::get('pk10/history/lottery', 'pk10/admin.Pk10Lottery/history');
    Route::get('egg/history/lottery', 'egg/admin.EggLottery/history');
    Route::get('cake/history/lottery', 'cake/admin.CakeLottery/history');

    // 下注历史
    Route::get('ssc/history/order', 'cqssc/admin.SscOrder/history');
    Route::get('pk10/history/order', 'pk10/admin.Pk10Order/history');
    Route::get('egg/history/order', 'egg/admin.EggOrder/history');
    Route::get('cake/history/order', 'cake/admin.CakeOrder/history');

    // 转盘相关
    Route::resource('ssc/tradlist', 'cqssc/admin.SscTradlist', ['except' => ['create', 'read']]);
    Route::resource('pk10/tradlist', 'pk10/admin.Pk10Tradlist', ['except' => ['create', 'read']]);
    Route::resource('egg/tradlist', 'egg/admin.EggTradlist', ['except' => ['create', 'read']]);
    Route::resource('cake/tradlist', 'cake/admin.CakeTradlist', ['except' => ['create', 'read']]);

    // 用户定制盘赔率
    Route::resource('ssc/customOdds', 'cqssc/admin.SscCustomOdds', ['only' => ['save']]);
    Route::resource('pk10/customOdds', 'pk10/admin.Pk10CustomOdds', ['only' => ['save']]);
    Route::resource('egg/customOdds', 'egg/admin.EggCustomOdds', ['only' => ['save']]);
    Route::resource('cake/customOdds', 'cake/admin.CakeCustomOdds', ['only' => ['save']]);

    // 玩法下注统计
    Route::get('ssc/markb', 'cqssc/admin.SscOrder/summaryMarkb');
    Route::get('pk10/markb', 'pk10/admin.Pk10Order/summaryMarkb');
    Route::get('egg/markb', 'egg/admin.EggOrder/summaryMarkb');
    Route::get('cake/markb', 'cake/admin.CakeOrder/summaryMarkb');

    // 手动开奖
    Route::post('ssc/manLottery', 'cqssc/admin.SscLottery/manLottery');
    Route::post('pk10/manLottery', 'pk10/admin.Pk10Lottery/manLottery');
    Route::post('egg/manLottery', 'egg/admin.EggLottery/manLottery');
    Route::post('cake/manLottery', 'cake/admin.CakeLottery/manLottery');

    // 
    Route::get('ssc/expects', 'cqssc/admin.SscLottery/getExpects');
});


/**
 * 前台路由
 */

// 用户表 相关操作路由
Route::resource('user', 'api/AccUsers', ['only' => ['save', 'edit']]);
Route::put('user', 'api/AccUsers/update');
Route::get('user', 'api/AccUsers/read');
// 用户修改密码
Route::put('password', 'api/AccUsers/updatePassword');
Route::put('password2', 'api/AccUsers/updatePassword2');

// 用户登录退出相关操作
Route::post('token', 'api/Token/login');
Route::delete('token', 'api/token/logout');

Route::resource('articles', 'api/AccArticles', ['only' => ['index', 'read']]);

// 充值表 相关操作路由
Route::resource('topup', 'api/AccTopup', ['only' => ['index', 'read', 'save']]);

// 提现表 相关操作路由
Route::resource('withdraw', 'api/AccWithdraw', ['only' => ['index', 'read', 'save']]);

// 提现绑定表 相关操作路由
Route::resource('banks', 'api/AccBanks', ['except' => ['create', 'edit']]);


// 用户资金详情
Route::get('money', 'api/AccMoney/read');
Route::get('getMoney', 'api/AccMoney/getMoney');

// 用户资金变动相关
Route::get('chgs', 'api/AccMychg/index');

// 刷新令牌
Route::any('refreshToken', 'api/Token/refreshToken');

// 用户注册时的用户名有效性检测接口
Route::get('checkUsername', 'api/AccUsers/checkUsername');

// 判断用户是否登录
Route::get('ifLogin', 'auth/Base/checkLogin');

// 用户登录记录列表
Route::get('loginList', 'api/AccLogin/index');

// 用户注单汇总报表
Route::get('summary', 'api/SummaryData/index');
// 结算报表
Route::get('clearList', 'api/SummaryData/clearList');
Route::get('clearList2', 'api/SummaryData/clearList2');
Route::get('details', 'api/SummaryData/detail');


// 获取服务器域名
Route::get('host', 'index/Test/host');

/**
 * 时时彩相关路由
 */
Route::group('ssc', function () {

    // 获取用户专属赔率
    Route::get('odds/:type', 'cqssc/Ssc/odds');
    Route::get('pans', 'cqssc/SscRatelist/read');

    // 时时彩时间管理
    Route::get('time', 'cqssc/SscTimelist/time');

    // 时时彩最新开奖结果
    Route::get('lastLty', 'cqssc/Kaijiang/lastLty');
    Route::get('history/lottery', 'cqssc/SscLottery/history');

    // 用户下注时时彩
    Route::post('order', 'cqssc/SscOrder/save');
    Route::delete('order', 'cqssc/SscOrder/delete');

    // 用户下注历史列表
    Route::get('history', 'cqssc/SscOrder/history');

    Route::get('summary', 'cqssc/SscOrder/summary');
    Route::get('detail', 'cqssc/SscOrder/detail');

    // 用户注额信息
    Route::get('tradInfo', 'cqssc/SscTradlist/getTradInfo');

    // 长龙统计
    Route::get('longDragon', 'cqssc/Kaijiang/longDragon');
});

/**
 * PK拾相关路由
 */
Route::group('pk10', function () {

    // 获取用户专属赔率
    Route::get('odds/:type', 'pk10/Pk10/odds');
    Route::get('pans', 'pk10/Pk10Ratelist/read');

    // PK拾时间管理
    Route::get('time', 'pk10/Pk10Timelist/time');

    // PK拾最新开奖结果
    Route::get('lastLty', 'pk10/Kaijiang/lastLty');
    Route::get('history/lottery', 'pk10/Pk10Lottery/history');

    // 用户下注PK拾
    Route::post('order', 'pk10/Pk10Order/save');
    Route::delete('order', 'pk10/Pk10Order/delete');

    // 用户下注历史列表
    Route::get('history', 'pk10/Pk10Order/history');

    Route::get('summary', 'pk10/Pk10Order/summary');

    Route::get('detail', 'pk10/Pk10Order/detail');

    // 用户注额信息
    Route::get('tradInfo', 'pk10/Pk10Tradlist/getTradInfo');

    // 长龙统计
    Route::get('longDragon', 'pk10/Kaijiang/longDragon');
});


/**
 * PC蛋蛋
 */
Route::group('egg', function () {

    // 获取用户专属赔率
    Route::get('odds', 'egg/Egg/odds');
    Route::get('pans', 'egg/EggRatelist/read');

    // PC蛋蛋时间管理
    Route::get('time', 'egg/EggTimelist/time');

    // PC蛋蛋最新开奖结果
    Route::get('lastLty', 'egg/Kaijiang/lastLty');
    Route::get('history/lottery', 'egg/EggLottery/history');

    // 用户下注PC蛋蛋
    Route::post('order', 'egg/EggOrder/save');
    Route::delete('order', 'egg/EggOrder/delete');

    // 用户下注历史列表
    Route::get('history', 'egg/EggOrder/history');

    Route::get('summary', 'egg/EggOrder/summary');

    Route::get('detail', 'egg/EggOrder/detail');

    // 用户注额信息
    Route::get('tradInfo', 'egg/EggTradlist/getTradInfo');

    // 长龙统计
    Route::get('longDragon', 'egg/Kaijiang/longDragon');
});


/**
 * 加拿大28
 */
Route::group('cake', function () {

    // 获取用户专属赔率
    Route::get('odds', 'cake/Cake/odds');
    Route::get('pans', 'cake/CakeRatelist/read');

    // 加拿大28时间管理
    Route::get('time', 'cake/CakeTimelist/time');

    // 加拿大28最新开奖结果
    Route::get('lastLty', 'cake/Kaijiang/lastLty');
    Route::get('history/lottery', 'cake/CakeLottery/history');

    // 用户下注加拿大28
    Route::post('order', 'cake/CakeOrder/save');
    Route::delete('order', 'cake/CakeOrder/delete');

    // 用户下注历史列表
    Route::get('history', 'cake/CakeOrder/history');

    Route::get('summary', 'cake/CakeOrder/summary');

    Route::get('detail', 'cake/CakeOrder/detail');

    // 用户注额信息
    Route::get('tradInfo', 'cake/CakeTradlist/getTradInfo');

    // 长龙统计
    Route::get('longDragon', 'cake/Kaijiang/longDragon');
});



// 清空缓存
//Route::any('test', 'index/Test/test');

//return [
//    '__pattern__' => [
//        'name' => '\w+',
//    ],
//    '[hello]' => [
//        ':id' => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
//        ':name' => ['index/hello', ['method' => 'post']],
//    ],
//
//];
