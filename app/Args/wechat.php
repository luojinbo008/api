<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/10/19
 * Time: 14:52
 */

return [
    'authorization.json' => [
        'name' => "微信公众号授权",
        'type' => 'GET',
        'args' => [
            
        ],
    ],
    'getThirdWechatInfo.json' => [
        'name' => "获得第三方微信账号授权信息",
        'type' => 'GET',
        'args' => [
            'authCodeValue' => 'string'
        ],
    ],
    'getConfig.json'    => [
        'name' => "获得授权方微信公众号配置",
        'type' => 'GET',
        'args' => [
        ],
    ],
    'unAuthorization.json'  => [
        'name' => "取消授权方微信公众号",
        'type' => 'POST',
        'args' => [
        ],
    ],
    'getUpStreamMsg.json'  => [
        'name' => "获取消息发送数据统计",
        'type' => 'GET',
        'args' => [
        ],
    ],
];