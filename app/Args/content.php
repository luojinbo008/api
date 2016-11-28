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
    'addBlogCategory.json'  => [
        'name' => "新增博客分类",
        'type' => 'POST',
        'args' => [
            'blog_category_name'                => 'string|empty',
            'blog_category_meta_title'          => 'string|empty',
            'blog_category_description'         => 'string',
            'blog_category_meta_description'    => 'string',
            'blog_category_meta_keyword'        => 'string',
            'parent_id'                         => 'int',
            'blog_category_store'               => 'array',
            'image'                             => 'string',
            'sort_order'                        => 'int',
            'status'                            => 'int',
        ],
    ],
    'getBlogCategory.json'   => [
        'name' => "获得博客分类信息",
        'type' => 'GET',
        'args' => [
            'blog_category_id'  => 'int|empty',
            'get_store'         => 'int',
        ],
    ],
    'editBlogCategory.json'  => [
        'name' => "编辑博客分类",
        'type' => 'POST',
        'args' => [
            'blog_category_id'                  => 'int|empty',
            'blog_category_name'                => 'string|empty',
            'blog_category_meta_title'          => 'string|empty',
            'blog_category_description'         => 'string',
            'blog_category_meta_description'    => 'string',
            'blog_category_meta_keyword'        => 'string',
            'parent_id'                         => 'int',
            'blog_category_store'               => 'array',
            'image'                             => 'string',
            'sort_order'                        => 'int',
            'status'                            => 'int',
        ],
    ],
    'repairBlogCategory.json'  => [
        'name' => "重构博客分类树状结构",
        'type' => 'GET',
        'args' => [
        ],
    ],
    'deleteBlogCategory.json'  => [
        'name' => "删除博客分类",
        'type' => 'POST',
        'args' => [
            'blog_category_ids'      => 'array|empty'
        ],
    ],
];
