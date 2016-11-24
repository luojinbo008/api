<?php
/**
 * 网站内容 博客 + 新闻
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/11/23
 * Time: 10:50
 */
return [
    'getBlogCategoryList.json' => [
        'name' => "获得博客分类列表",
        'type' => 'GET',
        'args' => [
            'filter_name'   => 'string',
            'sort'          => 'string',
            'order'         => 'string',
            'start'         => 'int',
            'limit'         => 'int',
        ],
    ],
];
