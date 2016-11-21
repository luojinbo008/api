<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/8/25
 * Time: 14:46
 */
$controller = '\\App\\Controllers\\OrderController';

// 获得订单列表
$app->get('/order/getOrderList.json', $controller . ':getOrderList');
$app->get('/order/getOrderInfo.json', $controller . ':getOrderInfo');
$app->get('/order/getOrderHistories.json', $controller . ':getOrderHistories');
$app->get('/order/getOrderStatistics.json', $controller . ':getOrderStatistics');
$app->get('/order/getOrderStatisticsDetail.json', $controller . ':getOrderStatisticsDetail');

// 商城接口
$app->get('/order/getMyOrderList.json', $controller . ':getMyOrderList');
$app->get('/order/getMyOrderInfo.json', $controller . ':getMyOrderInfo');
$app->post('/order/addOrderByProduct.json', $controller . ':addOrderByProduct');
$app->post('/order/checkShippingAddress.json', $controller . ':checkShippingAddress');
$app->post('/order/payOrder.json', $controller . ':payOrder');
$app->post('/order/refundOrder.json', $controller . ':refundOrder');
$app->post('/order/refundSubmitOrder.json', $controller . ':refundSubmitOrder');

// 订单回调
$app->post('/order/submitOrder.json', $controller . ':submitOrder');

