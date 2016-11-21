<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/11/18
 * Time: 19:13
 */
$controller = '\\App\\Controllers\\NotifyController';
$app->get('/notify/alipay/{appid}', $controller . ':alipay');
$app->get('/notify/wechat/{appid}', $controller . ':wechat');
