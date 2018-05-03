<?php
//配置文件
return [
    'jwt_key' => md5('jwt_key'),
    'base_white_list' => [
        'Index/index',
        'Token/login',
        'Kaijiang/lastLty',
        'AccUsers/save',
        'SscOrder/save2',
        'EggSscOrder/save2',
        'CakeOrder/save2',
        'Pk10Order/save2',
        'AccMoney/getMoney',
        'SscTradlist/getTradInfo',
        'EggTradlist/getTradInfo',
        'CakeTradlist/getTradInfo',
        'Pk10Tradlist/getTradInfo',
        'AccUsers/checkUsername',
        'Kaijiang/collectlty'
    ],
    'admin_base_white_list' => [
        'Admin.Token/login',
        'Kaijiang/lastLty',
        'AccUsers/save',
        'AdminIndex/index',
//        'Admin.AccArticles/upload'
    ]
];