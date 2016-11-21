<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-17
 * Time: 下午4:51
 */
return [
    'getStoreList.json' => [
        'name' => "获得商店列表",
        'type' => 'GET',
        'args' => [
            'start' => 'int',
            'limit' => 'int',
        ],
    ],
    'getStoreInfo.json' => [
        'name' => "获得商店信息",
        'type' => 'GET',
        'args' => [
            'type'              => 'string',
            'value'             => 'empty',
        ],
    ],
    'addStore.json' => [
        'name' => "新增商店",
        'type' => 'POST',
        'args' => [
            'store_url'          => 'string|empty',
            'meta_title'         => 'string|empty',
            'meta_description'   => 'string',
            'meta_keyword'       => 'string',
            'name'               => 'string|empty',
            'store_type'         => 'string|empty',
            'image'              => 'string',
            'comment'            => 'string',
            'customer_group_id'  => 'int|empty',
            'stock_display'      => 'int',

        ],
    ],
    'uploadStore.json' => [
        'name' => "编辑商店",
        'type' => 'POST',
        'args' => [
            'store_id'           => 'int|empty',
            'store_url'          => 'string|empty',
            'meta_title'         => 'string|empty',
            'meta_description'   => 'string',
            'meta_keyword'       => 'string',
            'name'               => 'string|empty',
            'store_type'         => 'string|empty',
            'image'              => 'string',
            'comment'            => 'string',
            'customer_group_id'  => 'int|empty',
            'stock_display'      => 'int',
            'advert_image'       => 'array'
        ],
    ],
    'deleteStore.json'  => [
        'name' => "删除商店",
        'type' => 'GET',
        'args' => [
            'store_ids'         => 'array|empty',
        ],
    ],
    'getPaymentSetting.json' => [
        'name' => "获得支付配置",
        'type' => 'GET',
        'args' => [
            'type'  => 'string|empty',
        ],
    ],
    'setPaymentSetting.json' => [
        'name' => "设置支付配置",
        'type' => 'POST',
        'args' => [
            'type'  => 'string|empty',
            'data'  => 'array|empty'
        ],
    ],
    'getCityList.json'  => [
        'name' => "获得城市列表",
        'type' => 'GET',
        'args' => [
            'zone_id'       => 'int|empty',
        ],
    ],
    'getZoneList.json'  => [
        'name' => "获得区域列表",
        'type' => 'GET',
        'args' => [
            'country_id'    => 'int|empty',
        ],
    ],
    'getMenus.json'  => [
        'name' => "获得菜单列表",
        'type' => 'GET',
        'args' => [
        ],
    ],
];