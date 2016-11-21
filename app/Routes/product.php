<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-13
 * Time: 下午2:18
 */

$controller = '\\App\\Controllers\\ProductController';
$app->get('/product/getStockStatus.json', $controller . ':getStockStatus');
// 具体商店入口
$app->get('/product/getProductsByStore.json', $controller . ':getProductsByStore');
$app->get('/product/getProductInfoByStore.json', $controller . ':getProductInfoByStore');

// 商品选项
$app->get('/product/getProductInfo.json', $controller . ':getProductInfo');
$app->get('/product/getOptionList.json', $controller . ':getOptionList');
$app->get('/product/getOptionInfo.json', $controller . ':getOptionInfo');
$app->get('/product/deleteOption.json', $controller . ':deleteOption');
$app->post('/product/addOption.json', $controller . ':addOption');
$app->post('/product/updateOption.json', $controller . ':updateOption');

// 商品管理
$app->get('/product/getProductList.json', $controller . ':getProductList');
$app->post('/product/addProduct.json', $controller . ':addProduct');
$app->post('/product/updateProduct.json', $controller . ':updateProduct');
$app->get('/product/deleteProduct.json', $controller . ':deleteProduct');

// 商品分类
$app->get('/product/getStoreCategoryList.json', $controller . ':getStoreCategoryList');
$app->get('/product/getStoreTopCategoryList.json', $controller . ':getStoreTopCategoryList');
$app->get('/product/getCategoryList.json', $controller . ':getCategoryList');
$app->get('/product/getCategory.json', $controller . ':getCategory');
$app->post('/product/addCategory.json', $controller . ':addCategory');
$app->post('/product/editCategory.json', $controller . ':editCategory');
$app->get('/product/deleteCategory.json', $controller . ':deleteCategory');
$app->get('/product/repairCategory.json', $controller . ':repairCategory');

// 筛选分组
$app->post('/product/addFilterGroup.json', $controller . ':addFilterGroup');
$app->post('/product/editFilterGroup.json', $controller . ':editFilterGroup');
$app->get('/product/getGroupInfo.json', $controller . ':getGroupInfo');
$app->get('/product/getGroupList.json', $controller . ':getGroupList');
$app->get('/product/deleteFilterGroup.json', $controller . ':deleteFilterGroup');
$app->get('/product/getFilterList.json', $controller . ':getFilterList');

// 品牌-制造商
$app->get('/product/getManufacturerList.json', $controller . ':getManufacturerList');
$app->get('/product/getManufacturerInfo.json', $controller . ':getManufacturerInfo');
$app->post('/product/addManufacturer.json', $controller . ':addManufacturer');
$app->post('/product/updateManufacturer.json', $controller . ':updateManufacturer');
$app->post('/product/deleteManufacturer.json', $controller . ':deleteManufacturer');
