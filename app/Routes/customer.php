<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-8-21
 * Time: 下午6:19
 */
$controller = '\\App\\Controllers\\CustomerController';
// 客户
$app->get('/customer/getCustomerList.json', $controller . ':getCustomerList');
$app->get('/customer/getCustomerInfo.json', $controller . ':getCustomerInfo');
$app->get('/customer/getStoreCustomerInfo.json', $controller . ':getStoreCustomerInfo');
$app->get('/customer/getCustomerGroupList.json', $controller . ':getCustomerGroupList');
$app->post('/customer/updateCustomerInfo.json', $controller . ':updateCustomerInfo');
$app->get('/customer/getCustomerStatistics.json', $controller . ':getCustomerStatistics');
$app->get('/customer/getCustomerStatisticsDetail.json', $controller . ':getCustomerStatisticsDetail');
$app->get('/customer/getStoreCustomerPoints.json', $controller . ':getStoreCustomerPoints');

$app->post('/customer/registerByWechat.json', $controller . ':registerByWechat');
$app->post('/customer/addCustomerAddress.json', $controller . ':addCustomerAddress');
$app->get('/customer/getCustomerAddressList.json', $controller . ':getCustomerAddressList');
$app->post('/customer/setAddressDefault.json', $controller . ':setAddressDefault');
$app->get('/customer/getAddressInfo.json', $controller . ':getAddressInfo');
$app->post('/customer/updateCustomerAddress.json', $controller . ':updateCustomerAddress');
$app->post('/customer/deleteAddress.json', $controller . ':deleteAddress');

// 购物车
$app->get('/customer/getCartProducts.json', $controller . ':getCartProducts');
$app->get('/customer/getCartProductCountByCustomer.json', $controller . ':getCartProductCountByCustomer');
$app->post('/customer/addProductToCart.json', $controller . ':addProductToCart');
$app->post('/customer/changeCartProductQuantity.json', $controller . ':changeCartProductQuantity');
$app->post('/customer/checkoutCart.json', $controller . ':checkoutCart');
$app->post('/customer/deleteCart.json', $controller . ':deleteCart');
