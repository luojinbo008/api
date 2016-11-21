<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-17
 * Time: 下午4:46
 */
$controller = '\\App\\Controllers\\SettingController';
$app->get('/setting/getStoreList.json', $controller . ':getStoreList');
$app->get('/setting/getStoreInfo.json', $controller . ':getStoreInfo');
$app->post('/setting/addStore.json', $controller . ':addStore');
$app->post('/setting/uploadStore.json', $controller . ':uploadStore');
$app->get('/setting/deleteStore.json', $controller . ':deleteStore');

$app->get('/setting/getCityList.json', $controller . ':getCityList');
$app->get('/setting/getZoneList.json', $controller . ':getZoneList');
$app->get('/setting/getMenus.json', $controller . ':getMenus');

$app->get('/setting/getPaymentSetting.json', $controller . ':getPaymentSetting');
$app->post('/setting/setPaymentSetting.json', $controller . ':setPaymentSetting');