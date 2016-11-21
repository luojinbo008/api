<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-8-7
 * Time: 下午5:39
 */

namespace Lib\Model\Customer;

use Lib\Model\BaseModel;
use Lib\Model\Product\ProductModel;
use Lib\Model\Product\ProductOptionModel;
use Lib\Model\Product\ProductOptionValueModel;

class CartModel extends BaseModel
{
    /**
     * 获得购物车商品明细
     * @param $appid
     * @param $store_id
     * @param $session_id
     * @param $customer_id
     * @param $customer_group_id
     * @param array $cart_id
     * @return array
     */
    public function getCartProductsByCustomer($appid, $store_id, $session_id, $customer_id,
                                              $customer_group_id, $cart_id = [])
    {
        $product_data = [];
        $where =  [
            'AND' => [
                'appid'         => (int)$appid,
                'store_id'      => (int)$store_id,
                'customer_id'   => (int) $customer_id,
                'session_id'    => $session_id
            ]
        ];
        if (!empty($cart_id)) {
            $where['AND']['cart_id'] = $cart_id;
        }
        $rows = $this->db->select('mcc_cart', '*',$where);

        foreach ($rows as $cart) {
            $stock = true;
            $productModel = new ProductModel();
            $product_info = $productModel->getProductDetailByCustomerGroupId($appid, $store_id, $cart['product_id'], $customer_group_id);
            if ($product_info) {
                $option_price = 0;
                $option_points = 0;
                $option_data = [];
                foreach (json_decode($cart['option'], true) as $product_option_id => $value) {
                    $option_rows = $productModel->getProductOption($appid, $cart['product_id'], $product_option_id);
                    if (count($option_rows) > 0) {
                        $option_row = $option_rows[0];
                        if ($option_row['type'] == 'select' || $option_row['type'] == 'radio' || $option_row['type'] == 'image') {
                            $product_option_value_rows = $productModel->getProductOptionValue($appid, $value, $product_option_id);
                            if (count($product_option_value_rows) > 0) {
                                $product_option_value_row = $product_option_value_rows[0];
                                if ($product_option_value_row['price_prefix'] == '+') {
                                    $option_price += $product_option_value_row['price'];
                                } elseif ($product_option_value_row['price_prefix'] == '-') {
                                    $option_price -= $product_option_value_row['price'];
                                }
                                if ($product_option_value_row['points_prefix'] == '+') {
                                    $option_points += $product_option_value_row['points'];
                                } elseif ($product_option_value_row['points_prefix'] == '-') {
                                    $option_points -= $product_option_value_row['points'];
                                }

                                if ($product_option_value_row['subtract'] && (!$product_option_value_row['quantity']
                                        || ($product_option_value_row['quantity'] < $cart['quantity']))) {
                                    $stock = false;
                                }

                                $option_data[] = [
                                    'product_option_id'       => $product_option_id,
                                    'product_option_value_id' => $value,
                                    'option_id'               => $option_row['option_id'],
                                    'option_value_id'         => $product_option_value_row['option_value_id'],
                                    'name'                    => $option_row['name'],
                                    'value'                   => $product_option_value_row['name'],
                                    'type'                    => $option_row['type'],
                                    'quantity'                => $product_option_value_row['quantity'],
                                    'subtract'                => $product_option_value_row['subtract'],
                                    'price'                   => $product_option_value_row['price'],
                                    'price_prefix'            => $product_option_value_row['price_prefix'],
                                    'points'                  => $product_option_value_row['points'],
                                    'points_prefix'           => $product_option_value_row['points_prefix'],
                                ];
                            }
                        } elseif ($option_row['type'] == 'checkbox' && is_array($value)) {
                            foreach ($value as $product_option_value_id) {
                                $product_option_value_rows = $productModel->getProductOptionValue($appid, $product_option_value_id, $product_option_id);
                                if (count($product_option_value_rows) > 0) {
                                    $product_option_value_row = $product_option_value_rows[0];
                                    if ($product_option_value_row['price_prefix'] == '+') {
                                        $option_price += $product_option_value_row['price'];
                                    } elseif ($product_option_value_row['price_prefix'] == '-') {
                                        $option_price -= $product_option_value_row['price'];
                                    }
                                    if ($product_option_value_row['points_prefix'] == '+') {
                                        $option_points += $product_option_value_row['points'];
                                    } elseif ($product_option_value_row['points_prefix'] == '-') {
                                        $option_points -= $product_option_value_row['points'];
                                    }
                                    if ($product_option_value_row['subtract'] && (!$product_option_value_row['quantity'] || ($product_option_value_row['quantity'] < $cart['quantity']))) {
                                        $stock = false;
                                    }
                                    $option_data[] = [
                                        'product_option_id'       => $product_option_id,
                                        'product_option_value_id' => $product_option_value_id,
                                        'option_id'               => $option_row['option_id'],
                                        'option_value_id'         => $product_option_value_row['option_value_id'],
                                        'name'                    => $option_row['name'],
                                        'value'                   => $product_option_value_row->row['name'],
                                        'type'                    => $option_row['type'],
                                        'quantity'                => $product_option_value_row['quantity'],
                                        'subtract'                => $product_option_value_row['subtract'],
                                        'price'                   => $product_option_value_row['price'],
                                        'price_prefix'            => $product_option_value_row['price_prefix'],
                                        'points'                  => $product_option_value_row['points'],
                                        'points_prefix'           => $product_option_value_row['points_prefix'],
                                    ];
                                }
                            }
                        } elseif ($option_row['type'] == 'text' || $option_row['type'] == 'textarea' || $option_row['type'] == 'file'
                            || $option_row['type'] == 'date' || $option_row['type'] == 'datetime' || $option_row['type'] == 'time') {
                            $option_data[] = [
                                'product_option_id'       => $product_option_id,
                                'product_option_value_id' => '',
                                'option_id'               => $option_row['option_id'],
                                'option_value_id'         => '',
                                'name'                    => $option_row['name'],
                                'value'                   => $value,
                                'type'                    => $option_row['type'],
                                'quantity'                => '',
                                'subtract'                => '',
                                'price'                   => '',
                                'price_prefix'            => '',
                                'points'                  => '',
                                'points_prefix'           => '',

                            ];
                        }
                    }
                }
                $price = $product_info['price'];

                // 批发价格
                $discount_quantity = 0;
                foreach ($rows as $cart_2) {
                    if ($cart_2['product_id'] == $cart['product_id']) {
                        $discount_quantity += $cart_2['quantity'];
                    }
                }
                $where = [
                    "AND" => [
                        'appid'             => (int)$appid,
                        "product_id"        => (int)$cart['product_id'],
                        "customer_group_id" => (int)$customer_group_id,
                        "quantity[<=]"      => (int)$discount_quantity,
                        "OR"                => [
                            "date_start"        => '0000-00-00',
                            "date_start[<]"     => date("Y-m-d", CURRENT_TODAY)
                        ],
                        "OR"                => [
                            "date_end"          => '0000-00-00',
                            "date_end[>]"       => date("Y-m-d", CURRENT_TODAY)
                        ]
                    ],
                    "ORDER" => [
                        "quantity"  => "DESC",
                        "priority"  => "ASC",
                        "price"     => "ASC"
                    ]
                ];
                $product_discount_row = $this->db->get('mcc_product_discount', '*', $where);
                if ($product_discount_row) {
                    $price = $product_discount_row['price'];
                }

                // 优惠价
                $where = [
                    "AND" => [
                        "product_id"        => (int)$cart['product_id'],
                        "customer_group_id" => (int)$customer_group_id,
                        "OR"                => [
                            "date_start"        => '0000-00-00',
                            "date_start[<]"     => date("Y-m-d", CURRENT_TODAY)
                        ],
                        "OR"                => [
                            "date_end"          => '0000-00-00',
                            "date_end[>]"       => date("Y-m-d", CURRENT_TODAY)
                        ]
                    ],
                    "ORDER" => [
                        "priority"  => "DESC",
                        "price"     => "ASC",
                    ]
                ];

                $product_special_row = $this->db->get('mcc_product_special', '*', $where);
                if ($product_special_row) {
                    $price = $product_special_row['price'];
                }

                // Reward Points
                $where = [
                    "AND" => [
                        'appid'             => (int)$appid,
                        "product_id"        => (int)$cart['product_id'],
                        "customer_group_id" => (int)$customer_group_id
                    ]
                ];
                $product_reward_row = $this->db->get('mcc_product_reward', '*', $where);
                if ($product_reward_row) {
                    $reward = $product_reward_row['points'];
                } else {
                    $reward = 0;
                }
                // Stock

                if (!$product_info['quantity'] || ($product_info['quantity'] < $cart['quantity'])) {
                    $stock = false;
                }
                $product_data[] = [
                    'cart_id'         => $cart['cart_id'],
                    'product_id'      => $product_info['product_id'],
                    'name'            => $product_info['name'],
                    'image'           => $product_info['image'],
                    'option'          => $option_data,
                    'quantity'        => $cart['quantity'],
                    'minimum'         => $product_info['minimum'],
                    'subtract'        => $product_info['subtract'],
                    'stock'           => $stock,
                    'price'           => ($price + $option_price),
                    'total'           => ($price + $option_price) * $cart['quantity'],
                    'reward'          => $reward * $cart['quantity'],
                    'points'          => ($product_info['points'] ? ($product_info['points'] + $option_points) * $cart['quantity'] : 0),
                ];
            }
        }
        return $product_data;
    }

    /**
     * 获得购物车中-商品数量
     * @param $appid
     * @param $store_id
     * @param $session_id
     * @param $customer_id
     * @return mixed
     */
    public function getCartProductCountByCustomer($appid, $store_id, $session_id, $customer_id)
    {
        return $this->db->sum('mcc_cart', 'quantity', [
            'AND' => [
                'appid'         => (int)$appid,
                'store_id'      => (int)$store_id,
                'session_id'    => $this->escape($session_id),
                'customer_id'   => (int) $customer_id
            ]
        ]);
    }

    /**
     * 新增商品到购物车
     * @param $appid
     * @param $store_id
     * @param $session_id
     * @param $customer_id
     * @param $product_id
     * @param array $option
     * @param int $quantity
     */
    public function addCartProduct($appid, $store_id, $session_id, $customer_id, $product_id, $option = [], $quantity = 1)
    {
        $info = $this->db->get('mcc_cart', '*', [
            "AND" => [
                "appid"         => (int)$appid,
                'store_id'      => (int)$store_id,
                "customer_id"   => (int)$customer_id,
                "session_id"    => $this->escape($session_id),
                "product_id"    => (int)$product_id,
                "option"        => json_encode($option, true)
            ],
        ]);
        if (empty($info)) {
            $this->db->insert('mcc_cart', [
                "appid"         => (int)$appid,
                'store_id'      => (int)$store_id,
                'customer_id'       => (int)$customer_id,
                'session_id'        => $this->escape($session_id),
                'product_id'        => (int)$product_id,
                'quantity'          => (int)$quantity,
                '`option`'          => json_encode($option, true),
                'date_added'        => date("Y-m-d H:i:s", CURRENT_TIME),
            ]);
        } else {
            $this->db->update('mcc_cart', [
                'quantity[+]'  => (int)$quantity,
                'date_added'        => date("Y-m-d H:i:s", CURRENT_TIME),
            ], [
                "AND" => [
                    "appid"         => (int)$appid,
                    'store_id'      => (int)$store_id,
                    "customer_id"   => (int)$customer_id,
                    "session_id"    => $this->escape($session_id),
                    "product_id"    => (int)$product_id,
                    "option"        => json_encode($option, true)
                ],
            ]);
        }
    }

    /**
     * 更新购物车商品数量
     * @param $appid
     * @param $cart_id
     * @param $quantity
     * @return mixed
     */
    public function changeCartProductQuantity($appid, $cart_id, $quantity)
    {
        return $this->db->update('mcc_cart', [
            'quantity'          => (int)$quantity,
            'date_added'        => date("Y-m-d H:i:s", CURRENT_TIME),
        ], [
            "AND" => [
                "appid"         => (int)$appid,
                'cart_id'       => (int)$cart_id
            ],
        ]);
    }

    /**
     * 获得购物车商品信息
     * @param $appid
     * @param $cart_id
     * @return mixed
     */
    public function getCartInfoByCartId($appid, $cart_id)
    {
        return $this->db->get('mcc_cart', '*', [
            "AND" => [
                "appid"         => (int)$appid,
                'cart_id'       => (int)$cart_id
            ],
        ]);
    }

    /**
     * 删除购物车
     * @param $appid
     * @param $store_id
     * @param $session_id
     * @param $customer_id
     * @param $cart_id
     */
    public function deleteCart($appid, $store_id, $session_id, $customer_id, $cart_id)
    {
        $where = [
            'AND' => [
                "appid"       => (int)$appid,
                "store_id"    => (int)$store_id,
                "customer_id" => (int)$customer_id,
                "session_id"  => $this->escape($session_id)
            ]
        ];
        if (!empty($cart_id)) {
            $where['AND']['cart_id'] = $cart_id;
        }
        $this->db->delete('mcc_cart', $where);
    }
} 