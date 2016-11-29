<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/11/23
 * Time: 10:50
 */
$controller = '\\App\\Controllers\\ContentController';
$app->get('/content/getBlogCategoryList.json', $controller . ':getBlogCategoryList');
$app->post('/content/addBlogCategory.json', $controller . ':addBlogCategory');
$app->get('/content/getBlogCategory.json', $controller . ':getBlogCategory');
$app->post('/content/editBlogCategory.json', $controller . ':editBlogCategory');
$app->get('/content/repairBlogCategory.json', $controller . ':repairBlogCategory');
$app->get('/content/deleteBlogCategory.json', $controller . ':deleteBlogCategory');

$app->get('/content/getBlogList.json', $controller . ':getBlogList');