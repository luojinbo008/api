<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-17
 * Time: 下午4:51
 */
return [
    // 客户
    'deleteCart.json' => [
        'name' => "删除购物车",
        'type' => 'POST',
        'args' => [
            'store_id'          => 'int|empty',
            'customer_id'       => 'int|empty',
            'session_id'        => 'string|empty',
            'cart_id'           => 'int|empty',
        ],
    ],
    'getAddressInfo.json' => [
        'name' => "获得地址信息",
        'type' => 'GET',
        'args' => [
            'customer_id'           => 'int|empty',
            'address_id'            => 'int|empty',
        ],
    ],
    'deleteAddress.json' => [
        'name' => "删除地址信息信息",
        'type' => 'POST',
        'args' => [
            'store_id'              => 'int|empty',
            'customer_id'           => 'int|empty',
            'address_id'            => 'int|empty',
        ],
    ],
    'setAddressDefault.json' => [
        'name' => "设置默认地址",
        'type' => 'POST',
        'args' => [
            'customer_id'           => 'int|empty',
            'address_id'            => 'int|empty',
        ],
    ],
    'getCustomerAddressList.json' => [
        'name' => "获得用户地址列表",
        'type' => 'GET',
        'args' => [
            'customer_id'           => 'int|empty',
        ],
    ],
    'addCustomerAddress.json' => [
        'name' => "新增客户配送地址",
        'type' => 'POST',
        'args' => [
            'store_id'              => 'int|empty',
            'customer_id'           => 'int|empty',
            'fullname'              => 'string|empty',
            'shipping_telephone'    => 'string|empty',
            'company'               => 'string',
            'address'               => 'string',
            'postcode'              => 'string',
            'city'                  => 'string',
            'zone_id'               => 'int',
            'country_id'            => 'int',
            'custom_field'          => 'array',
            'default'               => 'int',
        ],
    ],
    'updateCustomerAddress.json' => [
        'name' => "编辑客户配送地址",
        'type' => 'POST',
        'args' => [
            'address_id'            => 'int|empty',
            'store_id'              => 'int|empty',
            'customer_id'           => 'int|empty',
            'fullname'              => 'string|empty',
            'shipping_telephone'    => 'string|empty',
            'company'               => 'string',
            'address'               => 'string',
            'postcode'              => 'string',
            'city'                  => 'string',
            'zone_id'               => 'int',
            'country_id'            => 'int',
            'custom_field'          => 'array',
            'default'               => 'int',
        ],
    ],
    'getStoreCustomerPoints.json' => [
        'name' => "获得客户积分",
        'type' => 'GET',
        'args' => [
            'customer_id'   => 'int|empty',
        ],
    ],
    'registerByWechat.json' => [
        'name' => "微信商场注册用户账号",
        'type' => 'POST',
        'args' => [
            'store_id'      => 'int|empty',
            'fullname'      => 'string|empty',
            'telephone'     => 'string|empty',
            'ip'            => 'string|empty',
            'idcard'        => 'string|empty',
            'open_id'       => 'string|empty',
            'nickname'      => 'string|empty',
        ],
    ],
    'getCustomerList.json' => [
        'name' => "获得客户列表",
        'type' => 'GET',
        'args' => [
            'filter_name'               => 'string',
            'filter_telephone'          => 'string',
            'filter_customer_group_id'  => 'string',
            'filter_status'             => 'string',
            'filter_date_added'         => 'string',
            'filter_ip'                 => 'string',
            'start'                     => 'int',
            'limit'                     => 'int',
        ],
    ],
    'getCustomerInfo.json' => [
        'name' => "获得客户信息",
        'type' => 'GET',
        'args' => [
            'customer_id'            => 'int|empty',
        ],
    ],
    'getStoreCustomerInfo.json' => [
        'name' => "获得商店客户信息",
        'type' => 'GET',
        'args' => [
            'store_id'  => 'int|empty',
            'type'      => 'string|empty',
            'value'     => 'empty',
        ],
    ],
    'getCustomerGroupList.json' => [
        'name' => "获得客户等级列表",
        'type' => 'GET',
        'args' => [
            'start' => 'int',
            'limit' => 'int',
            'sort'  => 'string',
            'order' => 'string',
        ],
    ],
    'updateCustomerInfo.json' => [
        'name' => "更新用户数据",
        'type' => 'POST',
        'args' => [
            'customer_id'       => 'int|empty',
            'customer_group_id' => 'int',
            'status'            => 'int',
            'fullname'          => 'string',
            'nickname'          => 'string',
            'telephone'         => 'string',
            'idcard'            => 'string',
        ],
    ],
    'getCustomerStatistics.json' => [
        'name' => "获得用户统计",
        'type' => 'GET',
        'args' => [
        ],
    ],
    'getCustomerStatisticsDetail.json' => [
        'name' => "年，月，周，日 统计明细",
        'type' => 'GET',
        'args' => [
            'type' => "string|empty"
        ],
    ],

    // 购物车
    'getCartProducts.json' => [
        'name' => "获得用户购物车商品",
        'type' => 'GET',
        'args' => [
            'store_id'      => 'int|empty',
            'session_id'    => 'string|empty',
            'customer_id'   => 'int|empty'
        ],
    ],
    'getCartProductCountByCustomer.json' => [
        'name' => "获得用户购物车商品数量",
        'type' => 'GET',
        'args' => [
            'store_id'      => 'int|empty',
            'session_id'    => 'string|empty',
            'customer_id'   => 'int|empty'
        ],
    ],
    'addProductToCart.json' => [
        'name' => "添加商品倒购物车",
        'type' => 'POST',
        'args' => [
            'store_id'      => 'int|empty',
            'product_id'    => 'int|empty',
            'customer_id'   => 'int|empty',
            'session_id'    => 'string|empty',
            'quantity'      => 'int|empty',
            'option'        => 'array',
        ],
    ],
    'changeCartProductQuantity.json' => [
        'name' => "更新购物车商品数量",
        'type' => 'POST',
        'args' => [
           'cart_id'    => 'int|empty',
           'quantity'   => 'int|empty',
        ],
    ],
    'checkoutCart.json' => [
        'name' => "checkout购物车",
        'type' => 'POST',
        'args' => [
            'store_id'      => 'int|empty',
            'customer_id'   => 'int|empty',
            'session_id'    => 'string|empty',
            'ip'            => 'string|empty',
            'cart_id'       => 'array|empty',
        ],
    ],
];