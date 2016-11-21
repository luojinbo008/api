<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/10/19
 * Time: 14:52
 */
$controller = '\\App\\Controllers\\WechatController';
$app->get('/wechat/authorization.json', $controller . ':authorization');
$app->get('/wechat/getThirdWechatInfo.json', $controller . ':getThirdWechatInfo');
$app->get('/wechat/getConfig.json', $controller . ':getConfig');
$app->post('/wechat/unAuthorization.json', $controller . ':unAuthorization');
$app->get('/wechat/getUpStreamMsg.json', $controller . ':getUpStreamMsg');


/**
 * 无需验证
 */
$app->post('/wechat/componentVerifyTicket', $controller . ':componentVerifyTicket');
$app->post('/wechat/response/{appid}', $controller . ':response');
$app->get('/wechat/redirect/{appid}/{path}', $controller . ':redirect');
