<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/8/25
 * Time: 14:46
 */
return [

    // 订单
    'refundSubmitOrder.json' => [
        'name' => "后台审核退款",
        'type' => 'POST',
        'args' => [
            'order_id'       => 'int|empty',
            'status'         => 'int|empty',
            'comment'        => 'string|empty',
        ],
    ],
    'refundOrder.json' => [
        'name' => "用户申请退款",
        'type' => 'POST',
        'args' => [
            'order_id'       => 'int|empty',
            'customer_id'    => 'int|empty',
            'comment'        => 'string|empty',
        ],
    ],
    'submitOrder.json' => [
        'name' => "订单回调",
        'type' => 'POST',
        'args' => [
            'payment'       => 'string|empty',
            'store_id'      => 'int|empty',
            'data'          => 'empty',
        ],
    ],
    'payOrder.json' => [
        'name' => "第三方下单支付",
        'type' => 'POST',
        'args' => [
            'order_id'      => 'int|empty',
            'payment'       => 'string|empty',
            'customer_id'   => 'int|empty',
            'name'          => 'string|empty'
        ],
    ],
    'checkShippingAddress.json' => [
        'name' => "选择订单联系地址",
        'type' => 'POST',
        'args' => [
            'customer_id'               => 'int|empty',
            'order_id'                  => 'int|empty',
            'address_id'                => 'int|empty',
            'shipping_address_format'   => 'string',
            'shipping_method'           => 'string',
            'shipping_code'             => 'string',
        ],
    ],
    'addOrderByProduct.json' => [
        'name' => "根据商品直接生成订单",
        'type' => 'POST',
        'args' => [
            'store_id'              => 'int|empty',
            'product_id'            => 'int|empty',
            'customer_id'           => 'int|empty',
            'quantity'              => 'int|empty',
            'ip'                    => 'string|empty',
            'option'                => 'array',
        ],
    ],
    'getMyOrderList.json' => [
        'name' => "商城用户获得订单列表",
        'type' => 'GET',
        'args' => [
            'store_id'              => 'int|empty',
            'customer_id'           => 'int|empty',
            'start'                 => 'int',
            'status'                => 'array',
            'limit'                 => 'int|empty'
        ],
    ],
    'getMyOrderInfo.json' => [
       'name' => "商城用户获得订单详细信息",
       'type' => 'GET',
       'args' => [
           'store_id'       => 'int|empty',
           'customer_id'    => 'int|empty',
           'order_id'       => 'int|empty'
       ],
    ],
    'getOrderList.json' => [
        'name' => "获得订单列表",
        'type' => 'GET',
        'args' => [
            'filter_order_status'   => 'int',
            'filter_order_id'       => 'int',
            'filter_customer'       => 'string',
            'filter_date_added'     => 'string',
            'filter_date_modified'  => 'string',
            'filter_total'          => 'float',
            'sort'                  => 'string',
            'order'                 => 'string',
            'start'                 => 'int',
            'limit'                 => 'int'
        ],
    ],
    'getOrderInfo.json' => [
        'name' => "获得订单信息",
        'type' => 'GET',
        'args' => [
            'order_id'  => 'int|empty'
        ],
    ],
    'getOrderHistories.json' => [
        'name' => "获得订单历史操作列表",
        'type' => 'GET',
        'args' => [
            'order_id'  => 'int|empty',
            'start'     => 'int',
            'limit'     => 'int'
        ],
    ],
    'getOrderStatistics.json' => [
        'name' => "订单统计",
        'type' => 'GET',
        'args' => [

        ],
    ],
    'getOrderStatisticsDetail.json' => [
        'name' => "年，月，周，日 统计明细",
        'type' => 'GET',
        'args' => [
            'type' => "string|empty"
        ],
    ]
];