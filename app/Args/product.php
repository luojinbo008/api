<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-13
 * Time: 下午2:17
 */
return [

    // 商品
    'getProductInfo.json'   => [
        'name' => "获得商品信息",
        'type' => 'GET',
        'args' => [
            'product_id'     => 'int',
        ]
    ],
    'addProduct.json'   => [
        'name' => "新增商品",
        'type' => 'POST',
        'args' => [
            'sku'               => 'string',
            'price'             => 'float|empty',
            'quantity'          => 'int|empty',
            'minimum'           => 'int|empty',
            'subtract'          => 'int|empty',
            'stock_status_id'   => 'int|empty',
            'name'              => 'string|empty',
            'meta_title'        => 'string|empty',
            'manufacturer_id'   => 'int',
            'shipping'          => 'int',
            'date_available'    => 'string',
            'status'            => 'int',
            'sort_order'        => 'int',
            'description'       => 'string',
            'tag'               => 'string',
            'meta_description'  => 'string',
            'meta_keyword'      => 'string',
            'image'             => 'string',
            'product_store'     => 'array',
            'product_option'    => 'array',
            'product_discount'  => 'array',
            'product_special'   => 'array',
            'product_image'     => 'array',
            'product_category'  => 'array',
            'product_filter'    => 'array',
            'product_reward'    => 'array|empty',
        ],
    ],
    'updateProduct.json'   => [
        'name' => "编辑商品",
        'type' => 'POST',
        'args' => [
            'product_id'        => 'int|empty',
            'sku'               => 'string',
            'price'             => 'float|empty',
            'quantity'          => 'int|empty',
            'minimum'           => 'int|empty',
            'subtract'          => 'int|empty',
            'stock_status_id'   => 'int|empty',
            'name'              => 'string|empty',
            'meta_title'        => 'string|empty',
            'manufacturer_id'   => 'int',
            'shipping'          => 'int',
            'date_available'    => 'string',
            'status'            => 'int',
            'sort_order'        => 'int',
            'description'       => 'string',
            'tag'               => 'string',
            'meta_description'  => 'string',
            'meta_keyword'      => 'string',
            'image'             => 'string',
            'product_store'     => 'array',
            'product_option'    => 'array',
            'product_discount'  => 'array',
            'product_special'   => 'array',
            'product_image'     => 'array',
            'product_category'  => 'array',
            'product_filter'    => 'array',
            'product_reward'    => 'array|empty',
        ],
    ],
    'deleteProduct.json'     => [
        'name' => "删除商品",
        'type' => 'GET',
        'args' => [
            'product_ids'           => 'array|empty'
        ]
    ],
    'getProductList.json'    => [
        'name' => "根据筛选条件获得商品列表",
        'type' => 'GET',
        'args' => [
            'filter_name'           => 'string',
            'filter_price'          => 'float',
            'filter_quantity'       => 'string',
            'filter_product_id'     => 'int',
            'filter_product_ids'    => 'array',
            'filter_status'         => 'int',
            'order'                 => 'int',
            'sort'                  => 'int',
            'start'                 => 'int',
            'limit'                 => 'int',
            'get_option'            => 'int',
        ],
    ],
    'getProductsByStore.json'   => [
        'name' => "商店-获得商品列表",
        'type' => 'GET',
        'args' => [
            'store_id'              => 'int|empty',
            'customer_group_id'     => 'int|empty',
            'start'                 => 'int',
            'limit'                 => 'int',
            'filter_category_id'    => 'int',
            'filter_name'           => 'string',
        ],
    ],
    'getProductInfoByStore.json' => [
        'name' => "商店-获得商品信息",
        'type' => 'GET',
        'args' => [
            'store_id'              => 'int|empty',
            'customer_group_id'     => 'int|empty',
            'product_id'            => 'int|empty',
        ],
    ],
    'getStockStatus.json'   => [
        'name' => "获得库存状态名称",
        'type' => 'GET',
        'args' => [
        ],
    ],
    // 选项
    'deleteOption.json'    => [
        'name' => "删除商品选项",
        'type' => 'GET',
        'args' => [
            'option_ids'    => 'array|empty',
        ],
    ],
    'addOption.json'    => [
        'name' => "新增商品选项",
        'type' => 'POST',
        'args' => [
            'name'          => 'string|empty',
            'type'          => 'type|empty',
            'sort_order'    => 'sort_order|int',
            'option_values' => 'array',
        ],
    ],
    'updateOption.json'    => [
        'name' => "编辑商品选项",
        'type' => 'POST',
        'args' => [
            'option_id'     => 'int|empty',
            'name'          => 'string|empty',
            'type'          => 'type|empty',
            'sort_order'    => 'sort_order|int',
            'option_values' => 'array',
        ],
    ],
    'getOptionList.json' => [
        'name' => "获得商品选项列表",
        'type' => 'GET',
        'args' => [
            'get_value'         => 'int',
            'filter_name'       => 'string',
            'filter_option_ids' => 'array',
            'start'             => 'int',
            'limit'             => 'int',
        ],
    ],
    'getOptionInfo.json' => [
        'name' => "获得商品选项信息",
        'type' => 'GET',
        'args' => [
            'option_id'         => 'int|empty',
        ],
    ],
    "getStoreTopCategoryList.json"  => [
        'name' => "商店-获得商品顶部分类(产品)列表",
        'type' => 'GET',
        'args' => [
            'store_id'          => 'int|empty',
            'customer_group_id' => 'int|empty',
            'product_limit'     => 'int|empty'
        ],
    ],
    // 分类
    'getStoreCategoryList.json' => [
        'name' => "商店-获得商品分类列表",
        'type' => 'GET',
        'args' => [
            'parent_id' => 'int',
            'store_id'  => 'int|empty',
        ],
    ],
    'getCategory.json' => [
        'name' => "获得商品分类信息",
        'type' => 'GET',
        'args' => [
            'category_id'   => 'int|empty',
            'get_store'     => 'int',
            'get_filter'    => 'int',
        ],
    ],
    'getCategoryList.json' => [
        'name' => "获得商品分类列表",
        'type' => 'GET',
        'args' => [
            'filter_category_ids'   => 'array',
            'filter_name'           => 'string',
            'sort'                  => 'string',
            'order'                 => 'string',
            'start'                 => 'int',
            'limit'                 => 'int',
        ],
    ],
    'addCategory.json' => [
        'name' => "新增商品分类",
        'type' => 'POST',
        'args' => [
            'image'             => 'string',
            'parent_id'         => 'int',
            'top'               => 'int',
            'column'            => 'int',
            'sort_order'        => 'int',
            'status'            => 'int',
            'name'              => 'string|empty',
            'description'       => 'string',
            'meta_title'        => 'string|empty',
            'meta_keyword'      => 'string',
            'meta_description'  => 'string',
            'filter_id'         => 'array',
            'store_id'          => 'array',
        ],
    ],
    'editCategory.json' => [
        'name' => "编辑商品分类",
        'type' => 'POST',
        'args' => [
            'category_id'       => 'int|empty',
            'image'             => 'string',
            'parent_id'         => 'int',
            'top'               => 'int',
            'column'            => 'int',
            'sort_order'        => 'int',
            'status'            => 'int',
            'name'              => 'string|empty',
            'description'       => 'string',
            'meta_title'        => 'string|empty',
            'meta_description'  => 'string',
            'meta_keyword'      => 'string',
            'filter_id'         => 'array',
            'store_id'          => 'array',
        ],
    ],
    'deleteCategory.json' => [
        'name' => "删除商品分类",
        'type' => 'GET',
        'args' => [
            'category_ids'      => 'array|empty'
        ],
    ],
    'repairCategory.json' => [
        'name' => "重构分类树形结构",
        'type' => 'GET',
        'args' => [
        ],
    ],

    // 筛选
    'addFilterGroup.json' => [
        'name' => "新增筛选分组",
        'type' => 'POST',
        'args' => [
            'sort_order'    => 'int',
            'group_name'    => 'string|empty',
            'filters'       => 'array|empty'
        ],
    ],
    'editFilterGroup.json' => [
        'name' => "编辑筛选分组",
        'type' => 'POST',
        'args' => [
            'group_id'      => 'int|empty',
            'sort_order'    => 'int',
            'group_name'    => 'string|empty',
            'filters'       => 'array|empty'
        ],
    ],
    'getGroupInfo.json' => [
        'name' => "获得筛选分组信息",
        'type' => 'GET',
        'args' => [
            'group_id'      => 'int|empty'
        ],
    ],
    'getGroupList.json' => [
        'name' => "获得筛选分组列表",
        'type' => 'GET',
        'args' => [
            'sort'      => 'string',
            'order'     => 'string',
            'start'     => 'int',
            'limit'     => 'int',
        ],
    ],
    'deleteFilterGroup.json' => [
        'name' => "删除筛选分组",
        'type' => 'GET',
        'args' => [
           'group_ids'      => 'array|empty'
        ],
    ],
    'getFilterList.json' => [
        'name' => "获得筛选列表",
        'type' => 'GET',
        'args' => [
            'filter_ids'    => 'array',
            'filter_name'   => 'string',
            'start'         => 'int',
            'limit'         => 'int',
        ],
    ],
    // 获得品牌-制造商
    'getManufacturerList.json' => [
        'name' => "获得品牌-制造商列表",
        'type' => 'GET',
        'args' => [
            'filter_name'   => 'string',
            'sort'          => 'string',
            'order'         => 'int',
            'start'         => 'int',
            'limit'         => 'int',
        ],
    ],
    'getManufacturerInfo.json' => [
        'name' => "获得品牌-制造商信息",
        'type' => 'GET',
        'args' => [
            'manufacturer_id'   => 'int|empty'
        ],
    ],
    'addManufacturer.json' => [
        'name' => "新增品牌-制造商列表",
        'type' => 'POST',
        'args' => [
            'name'                  => 'string|empty',
            'image'                 => 'string|empty',
            'sort_order'            => 'int',
            'manufacturer_store'    => 'array',
        ],
    ],
    'updateManufacturer.json' => [
        'name' => "编辑品牌-制造商列表",
        'type' => 'POST',
        'args' => [
            'manufacturer_id'       => 'int|empty',
            'name'                  => 'string|empty',
            'image'                 => 'string|empty',
            'sort_order'            => 'int',
            'manufacturer_store'    => 'array',
        ],
    ],
    'deleteManufacturer.json' => [
        'name' => "删除品牌-制造商列表",
        'type' => 'POST',
        'args' => [
            'manufacturer_ids'       => 'array|empty'
        ],
    ]
];