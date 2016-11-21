<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/8/22
 * Time: 11:15
 */

namespace Lib\Model\Product;

use Lib\Model\BaseModel;

class ProductModel extends BaseModel
{
    /**
     * 获得 商品实际价格等信息
     * @param $appid
     * @param $option
     * @param $customer_group_id
     * @param $product_id
     * @param $quantity
     * @param $price
     */
    public function getStoreProductInfoByOption($appid, $product_id, $option, $customer_group_id, $quantity)
    {
        $stock = true;
        $option_price = 0;
        $option_points = 0;
        $option_data = [];
        $productModel = new ProductModel();
        $product_info = $productModel->getProductInfo($appid, $product_id);
        foreach ($option as $product_option_id => $value) {
            $option_rows = $productModel->getProductOption($appid, $product_id, $product_option_id);
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
                                || ($product_option_value_row['quantity'] < $quantity))) {
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
                            if ($product_option_value_row['subtract'] && (!$product_option_value_row['quantity'] || ($product_option_value_row['quantity'] < $quantity))) {
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
        $where = [
            "AND" => [
                'appid'             => (int)$appid,
                "product_id"        => (int)$product_id,
                "customer_group_id" => (int)$customer_group_id,
                "quantity[<=]"       => (int)$quantity,
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
                "product_id"        => (int)$product_id,
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
                "product_id"        => (int)$product_id,
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
        if (!$product_info['quantity'] || ($product_info['quantity'] < $quantity)) {
            $stock = false;
        }
        return [
            'shipping'        => $product_info['shipping'],
            'product_id'      => $product_id,
            'name'            => $product_info['name'],
            'image'           => $product_info['image'],
            'option'          => $option_data,
            'minimum'         => $product_info['minimum'],
            'subtract'        => $product_info['subtract'],
            'stock'           => $stock,
            'quantity'        => $quantity,
            'price'           => ($price + $option_price),
            'total'           => ($price + $option_price) * $quantity,
            'reward'          => $reward * $quantity,
            'points'          => ($product_info['points'] ? ($product_info['points'] + $option_points) * $quantity : 0),
        ];
    }


    /**
     * 获得基本信息
     * @param $appid
     * @param $product_id
     * @return mixed
     */
    public function getProductInfo($appid, $product_id)
    {
        return $this->db->get('mcc_product', '*', [
            'AND'   => [
                'product_id'    => (int)$product_id,
                'appid'         => (int)$appid
            ]
        ]);
    }

    /**
     * 获得商品对应分类
     * @param $appid
     * @param $product_id
     * @return mixed
     */
    public function getProductToCategory($appid, $product_id)
    {
        $categories = $this->db->select('mcc_product_to_category', '*', [
            'AND'   => [
                'product_id'    => (int)$product_id,
                'appid'         => (int)$appid
            ]
        ]);
        $categories = array_column($categories, 'category_id');
        $product_categories = [];
        foreach ($categories as $category_id) {
            $sql = "SELECT DISTINCT *, (
                       SELECT GROUP_CONCAT(c1.name ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;')
                           FROM  mcc_category_path cp
                           LEFT JOIN mcc_category c1 ON (cp.path_id = c1.category_id AND cp.category_id != cp.path_id)
                           WHERE cp.category_id = c.category_id
                           GROUP BY cp.category_id) AS path
                       FROM mcc_category c
                       WHERE c.category_id = " . (int)$category_id . ' AND c.appid = ' . $appid;
            $category_list = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            if ($category_list) {
                foreach ($category_list as $category_info){
                    $product_categories[] = [
                        'category_id'   => $category_info['category_id'],
                        'name'          => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                    ];
                }
            }
        }
        return $product_categories;
    }

    /**
     * 获得商品 对应 筛选项
     * @param $appid
     * @param $product_id
     * @return array
     */
    public function getProductFilter($appid, $product_id)
    {
        $filters = $this->db->select('mcc_product_filter', '*', [
            'AND'   => [
                'product_id'    => (int)$product_id,
                'appid'         => (int)$appid
            ]
        ]);
        $filters = array_column($filters, 'filter_id');
        $product_filters = [];
        foreach ($filters as $filter_id) {
            $sql = "SELECT *, (SELECT name FROM mcc_filter_group fg
                    WHERE f.filter_group_id = fg.filter_group_id) AS `group`
                FROM mcc_filter f WHERE f.filter_id = " . (int)$filter_id;
            $filter_list = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            if ($filter_list) {
                foreach ($filter_list as $filter_info) {
                    $product_filters[] = [
                        'filter_id' => $filter_info['filter_id'],
                        'name'      => ($filter_info['group']) ? $filter_info['group'] . ' &gt; ' . $filter_info['name'] : $filter_info['name']
                    ];
                }
            }
        }
        return $product_filters;
    }

    /**
     * 获得商品 对应 商店
     * @param $appid
     * @param $product_id
     * @return array
     */
    public function getProductToStore($appid, $product_id)
    {
        $stores = $this->db->select('mcc_product_to_store', '*', [
            'AND'   => [
                'product_id'    => (int)$product_id,
                'appid'         => (int)$appid,
            ]
        ]);
        if (!$stores) {
            return [];
        }
        return array_column($stores, 'store_id');
    }

    /**
     * 获得商品关联
     * @param $appid
     * @param $product_id
     * @return array
     */
    public function getProductRelated($appid, $product_id)
    {
        $relateds = $this->db->select('mcc_product_related', '*', [
            'AND'   => [
                'product_id'    => (int)$product_id,
                'appid'         => (int)$appid,
            ]
        ]);
        if (!$relateds) {
            return [];
        }
        return array_column($relateds, 'related_id');
    }

    /**
     * 新增商品
     * @param $appid
     * @param $data
     * @return bool
     */
    public function addProduct($appid, $data)
    {
        $db = $this->db;
        $product_id = 0;
        $db->action(function($db) use ($appid, $data, &$product_id) {
            $last_product_id = $db->insert('mcc_product', [
                'appid'             => $appid,
                'sku'               => $data['sku'],
                'price'             => (float)$data['price'],
                'quantity'          => (int)$data['quantity'],
                'minimum'           => (int)$data['minimum'],
                'subtract'          => (int)$data['subtract'],
                'stock_status_id'   => (int)$data['stock_status_id'],
                'manufacturer_id'   => (int)$data['manufacturer_id'],
                'date_available'    => $data['date_available'],
                'status'            => (int)$data['status'],
                'sort_order'        => (int)$data['sort_order'],
                'name'              => $data['name'],
                'shipping'          => (int)$data['shipping'],
                'description'       => $data['description'],
                'tag'               => $data['tag'],
                'meta_title'        => $data['meta_title'],
                'meta_description'  => $data['meta_description'],
                'meta_keyword'      => $data['meta_keyword'],
                'image'             => $data['image'],
                'date_added'        => date("Y-m-d H:i:s", CURRENT_TIME),
                'date_modified'     => date("Y-m-d H:i:s", CURRENT_TIME)
            ]);
            if (!$last_product_id) {
                return false;
            }
            if (isset($data['product_store'])) {
                foreach ($data['product_store'] as $store_id) {
                    $db->insert('mcc_product_to_store', [
                        'appid'         => (int)$appid,
                        'store_id'      => (int)$store_id,
                        'product_id'    => (int)$last_product_id,
                    ]);
                }
            }
            if (isset($data['product_option'])) {
                foreach ($data['product_option'] as $product_option) {
                    if ($product_option['type'] == 'select' || $product_option['type'] == 'radio'
                        || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                        if (isset($product_option['product_option_value'])) {
                            $product_option_id = $db->insert('mcc_product_option', [
                                'appid'             => (int)$appid,
                                'product_option_id' => (int)$product_option['product_option_id'],
                                'product_id'        => (int)$last_product_id,
                                'option_id'         => (int)$product_option['option_id'],
                                'required'          => (int)$product_option['required'],
                            ]);
                            foreach ($product_option['product_option_value'] as $product_option_value) {
                                $db->insert('mcc_product_option_value',[
                                    'appid'                     => (int)$appid,
                                    'product_option_value_id'   => (int)$product_option_value['product_option_value_id'],
                                    'product_option_id'         => (int)$product_option_id,
                                    'product_id'                => (int)$last_product_id,
                                    'option_id'                 => (int)$product_option['option_id'],
                                    'option_value_id'           => (int)$product_option_value['option_value_id'],
                                    'quantity'                  => (int)$product_option_value['quantity'],
                                    'subtract'                  => (int)$product_option_value['subtract'],
                                    'price'                     => (float)$product_option_value['price'],
                                    'price_prefix'              => $product_option_value['price_prefix'],
                                    'points'                    => (int)$product_option_value['points'],
                                    'points_prefix'             => $product_option_value['points_prefix'],
                                ]);
                            }
                        }
                    } else {
                        $db->insert('mcc_product_option', [
                            'appid'             => (int)$appid,
                            'product_option_id' => (int)$product_option['product_option_id'],
                            'product_id'        => (int)$last_product_id,
                            'option_id'         => (int)$product_option['option_id'],
                            'value'             => $product_option['value'],
                            'required'          => (int)$product_option['required'],
                        ]);
                    }
                }
            }
            if (isset($data['product_discount'])) {
                foreach ($data['product_discount'] as $product_discount) {
                    $db->insert('mcc_product_discount', [
                        'appid'             => (int)$appid,
                        'product_id'        => (int)$last_product_id,
                        'customer_group_id' => (int)$product_discount['customer_group_id'],
                        'quantity'          => (int)$product_discount['quantity'],
                        'priority'          => (int)$product_discount['priority'],
                        'price'             => (float)$product_discount['price'],
                        'date_start'        => $product_discount['date_start'],
                        'date_end'          => $product_discount['date_end'],
                    ]);
                }
            }
            if (isset($data['product_special'])) {
                foreach ($data['product_special'] as $product_special) {
                    $db->insert('mcc_product_special', [
                        'appid'             => (int)$appid,
                        'product_id'        => (int)$last_product_id,
                        'customer_group_id' => (int)$product_special['customer_group_id'],
                        'priority'          => (int)$product_special['priority'],
                        'price'             => (float)$product_special['price'],
                        'date_start'        => $product_special['date_start'],
                        'date_end'          => $product_discount['date_end'],
                    ]);
                }
            }
            if (isset($data['product_image'])) {
                foreach ($data['product_image'] as $product_image) {
                    $db->insert('mcc_product_image', [
                        'appid'             => (int)$appid,
                        'product_id'        => (int)$last_product_id,
                        'image'             => $product_image['image'],
                        'sort_order'        => (int)$product_image['sort_order'],
                    ]);
                }
            }
            if (isset($data['product_category'])) {
                foreach ($data['product_category'] as $category_id) {
                    $db->insert('mcc_product_to_category', [
                        'appid'             => (int)$appid,
                        'product_id'        => (int)$last_product_id,
                        'category_id'       => (int)$category_id,
                    ]);
                }
            }
            if (isset($data['product_filter'])) {
                foreach ($data['product_filter'] as $filter_id) {
                    $db->insert('mcc_product_filter', [
                        'appid'         => (int)$appid,
                        'product_id'    => (int)$last_product_id,
                        'filter_id'     => (int)$filter_id
                    ]);
                }
            }
            if (isset($data['product_related'])) {
                foreach ($data['product_related'] as $related_id) {
                    $db->delete('mcc_product_related', [
                        'AND' => [
                            'appid' => (int)$appid,
                            'OR'    => [
                                'AND'   => [
                                    'product_id'    => (int)$last_product_id,
                                    'related_id'    => (int)$related_id,
                                ],
                                'AND'   => [
                                    'related_id'    => (int)$last_product_id,
                                    'product_id'    => (int)$related_id,
                                ]
                            ]
                        ]
                    ]);
                    $db->insert('mcc_product_related', [
                        'appid'         => (int)$appid,
                        'product_id'    => (int)$related_id,
                        'related_id'    => (int)$last_product_id,
                    ]);
                }
            }
            if (isset($data['product_reward'])) {
                foreach ($data['product_reward'] as $customer_group_id => $value) {
                    if ((int)$value['points'] > 0) {
                        $db->insert('mcc_product_reward', [
                            'appid'             => (int)$appid,
                            'product_id'        => (int)$last_product_id,
                            'customer_group_id' => (int)$customer_group_id,
                            'points'            => (int)$value['points'],
                        ]);
                    }
                }
            }
            $product_id = $last_product_id;
            return true;
        });
        if ($product_id > 0) {
            return true;
        }
        return false;
    }

    /**
     * 更新商品
     * @param $appid
     * @param $product_id
     * @param $data
     * @return bool
     */
    public function updateProduct($appid, $product_id, $data)
    {
        $db = $this->db;
        $status = false;
        $db->action(function($db) use ($appid, $product_id, $data, &$status) {
            $status = $db->update('mcc_product', [
                'sku'               => $data['sku'],
                'price'             => (float)$data['price'],
                'quantity'          => (int)$data['quantity'],
                'minimum'           => (int)$data['minimum'],
                'subtract'          => (int)$data['subtract'],
                'shipping'          => (int)$data['shipping'],
                'stock_status_id'   => (int)$data['stock_status_id'],
                'manufacturer_id'   => (int)$data['manufacturer_id'],
                'date_available'    => $data['date_available'],
                'status'            => (int)$data['status'],
                'sort_order'        => (int)$data['sort_order'],
                'name'              => $data['name'],
                'description'       => $data['description'],
                'tag'               => $data['tag'],
                'meta_title'        => $data['meta_title'],
                'meta_description'  => $data['meta_description'],
                'meta_keyword'      => $data['meta_keyword'],
                'image'             => $data['image'],
                'date_modified'     => date("Y-m-d H:i:s", CURRENT_TIME),
            ], [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            if(!$status) {
                return false;
            }
            $db->delete('mcc_product_to_store', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            if (isset($data['product_store'])) {
                foreach ($data['product_store'] as $store_id) {
                    $db->insert('mcc_product_to_store', [
                        'appid'         => (int)$appid,
                        'store_id'      => (int)$store_id,
                        'product_id'    => (int)$product_id,
                    ]);
                }
            }
            $db->delete('mcc_product_option', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            $db->delete('mcc_product_option_value', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            if (isset($data['product_option'])) {
                foreach ($data['product_option'] as $product_option) {
                    if ($product_option['type'] == 'select' || $product_option['type'] == 'radio'
                        || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                        if (isset($product_option['product_option_value'])) {
                            $product_option_id = $db->insert('mcc_product_option', [
                                'appid'             => (int)$appid,
                                'product_option_id' => (int)$product_option['product_option_id'],
                                'product_id'        => (int)$product_id,
                                'option_id'         => (int)$product_option['option_id'],
                                'required'          => (int)$product_option['required'],
                            ]);
                            foreach ($product_option['product_option_value'] as $product_option_value) {
                                $db->insert('mcc_product_option_value',[
                                    'appid'                     => (int)$appid,
                                    'product_option_value_id'   => (int)$product_option_value['product_option_value_id'],
                                    'product_option_id'         => (int)$product_option_id,
                                    'product_id'                => (int)$product_id,
                                    'option_id'                 => (int)$product_option['option_id'],
                                    'option_value_id'           => (int)$product_option_value['option_value_id'],
                                    'quantity'                  => (int)$product_option_value['quantity'],
                                    'subtract'                  => (int)$product_option_value['subtract'],
                                    'price'                     => (float)$product_option_value['price'],
                                    'price_prefix'              => $product_option_value['price_prefix'],
                                    'points'                    => (int)$product_option_value['points'],
                                    'points_prefix'             => $product_option_value['points_prefix'],
                                ]);
                            }
                        }
                    } else {
                        $db->insert('mcc_product_option', [
                            'appid'             => (int)$appid,
                            'product_option_id' => (int)$product_option['product_option_id'],
                            'product_id'        => (int)$product_id,
                            'option_id'         => (int)$product_option['option_id'],
                            'value'             => $product_option['value'],
                            'required'          => (int)$product_option['required'],
                        ]);
                    }
                }
            }
            $db->delete('mcc_product_discount', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            if (isset($data['product_discount'])) {
                foreach ($data['product_discount'] as $product_discount) {
                    $db->insert('mcc_product_discount', [
                        'appid'             => (int)$appid,
                        'product_id'        => (int)$product_id,
                        'customer_group_id' => (int)$product_discount['customer_group_id'],
                        'quantity'          => (int)$product_discount['quantity'],
                        'priority'          => (int)$product_discount['priority'],
                        'price'             => (float)$product_discount['price'],
                        'date_start'        => $product_discount['date_start'],
                        'date_end'          => $product_discount['date_end'],
                    ]);
                }
            }
            $db->delete('mcc_product_special', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            if (isset($data['product_special'])) {
                foreach ($data['product_special'] as $product_special) {
                    $db->insert('mcc_product_special', [
                        'appid'             => (int)$appid,
                        'product_id'        => (int)$product_id,
                        'customer_group_id' => (int)$product_special['customer_group_id'],
                        'priority'          => (int)$product_special['priority'],
                        'price'             => (float)$product_special['price'],
                        'date_start'        => $product_special['date_start'],
                        'date_end'          => $product_discount['date_end'],
                    ]);
                }
            }
            $db->delete('mcc_product_image', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            if (isset($data['product_image'])) {
                foreach ($data['product_image'] as $product_image) {
                    $db->insert('mcc_product_image', [
                        'appid'             => (int)$appid,
                        'product_id'        => (int)$product_id,
                        'image'             => $product_image['image'],
                        'sort_order'        => (int)$product_image['sort_order'],
                    ]);
                }
            }
            $db->delete('mcc_product_to_category', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            if (isset($data['product_category'])) {
                foreach ($data['product_category'] as $category_id) {
                    $db->insert('mcc_product_to_category', [
                        'appid'             => (int)$appid,
                        'product_id'        => (int)$product_id,
                        'category_id'       => (int)$category_id,
                    ]);
                }
            }
            $db->delete('mcc_product_filter', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            if (isset($data['product_filter'])) {
                foreach ($data['product_filter'] as $filter_id) {
                    $db->insert('mcc_product_filter', [
                        'appid'         => (int)$appid,
                        'product_id'    => (int)$product_id,
                        'filter_id'     => (int)$filter_id
                    ]);
                }
            }
            $db->delete('mcc_product_related', [
                'AND'   => [
                    'appid' => $appid,
                    'OR'    => [
                        'product_id'    => (int)$product_id,
                        'related_id'    => (int)$product_id,
                    ]
                ]
            ]);
            if (isset($data['product_related'])) {
                foreach ($data['product_related'] as $related_id) {
                    $db->delete('mcc_product_related', [
                        'AND' => [
                            'appid' => (int)$appid,
                            'OR'    => [
                                'AND'   => [
                                    'product_id'    => (int)$product_id,
                                    'related_id'    => (int)$related_id,
                                ],
                                'AND'   => [
                                    'related_id'    => (int)$product_id,
                                    'product_id'    => (int)$related_id,
                                ]
                            ]
                        ]
                    ]);
                    $db->insert('mcc_product_related', [
                        'appid'         => (int)$appid,
                        'product_id'    => (int)$related_id,
                        'related_id'    => (int)$product_id,
                    ]);
                }
            }
            $db->delete('mcc_product_reward', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'product_id'    => (int)$product_id,
                ]
            ]);
            if (isset($data['product_reward'])) {
                foreach ($data['product_reward'] as $customer_group_id => $value) {
                    if ((int)$value['points'] > 0) {
                        $db->insert('mcc_product_reward', [
                            'appid'             => (int)$appid,
                            'product_id'        => (int)$product_id,
                            'customer_group_id' => (int)$customer_group_id,
                            'points'            => (int)$value['points'],
                        ]);
                    }
                }
            }
            $status = true;
            return true;
        });
        return $status;
    }

    /**
     * 删除商品
     * @param $appid
     * @param $product_ids
     */
    public function deleteProduct($appid, $product_ids)
    {
        $db = $this->db;
        $db->action(function($db) use ($appid, $product_ids) {
            $db->delete('mcc_product', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids
                ]
            ]);
            $db->delete('mcc_product_discount', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids
                ]
            ]);
            $db->delete('mcc_product_filter', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids
                ]
            ]);
            $db->delete('mcc_product_image', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids
                ]
            ]);
            $db->delete('mcc_product_option', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids
                ]
            ]);
            $db->delete('mcc_product_option_value', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids
                ]
            ]);
            $db->delete('mcc_product_related', [
                'AND' => [
                    'appid' => $appid,
                    'OR'    => [
                        'related_id'    => $product_ids,
                        'product_id'    => $product_ids,
                    ]
                ]
            ]);
            $db->delete('mcc_product_reward', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids,
                ]
            ]);
            $db->delete('mcc_product_special', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids,
                ]
            ]);
            $db->delete('mcc_product_to_category', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids,
                ]
            ]);
            $db->delete('mcc_product_to_store', [
                'AND' => [
                    'appid'         => $appid,
                    'product_id'    => $product_ids,
                ]
            ]);
            return true;
        });
        return true;
    }

    /**
     * 赛选获得商品列表
     * @param $appid
     * @param array $filter_data
     * @param null $start
     * @param null $limit
     * @return mixed
     */
    public function getProductsFilter($appid, $filter_data = [], $start = null, $limit = null)
    {
        $sql_count = 'SELECT count(1) FROM mcc_product WHERE appid = ' . $appid;
        $sql = 'SELECT * FROM mcc_product WHERE appid = ' . $appid;
        $where = [];
        if (!empty($filter_data['filter_name'])) {
            $where[] = " name LIKE '%" . $filter_data['filter_name']. "%'";
        }
        if (isset($filter_data['filter_price']) && !empty($filter_data['filter_price']) && !is_null($filter_data['filter_price'])) {
            $where[] = " price LIKE '" . $filter_data['filter_price'] . "%'";
        }
        if (isset($filter_data['filter_quantity']) && !empty($filter_data['filter_quantity']) && !is_null($filter_data['filter_quantity'])) {
            $where[] = " quantity = '" . (int)$filter_data['filter_quantity'] . "'";
        }
        if (isset($filter_data['filter_status']) && !is_null($filter_data['filter_status'])) {
            $where[] = " status = '" . (int)$filter_data['filter_status'] . "'";
        }
        if (isset($filter_data['filter_product_id']) && !empty($filter_data['filter_product_id'])) {
            $where[] = " product_id = '" . (int)$filter_data['filter_product_id'] . "'";
        }
        if (isset($filter_data['filter_product_ids']) && !empty($filter_data['filter_product_ids'])) {
            $where[] = " product_id in (" . implode(',', $filter_data['filter_product_ids']) . ")";
        }

        if(count($where) > 0){
            $sql_count .= " AND " . implode(' AND ', $where);
            $sql .= " AND " . implode(' AND ', $where);
        }
        $count = $this->db->query($sql_count)
            ->fetchColumn();
        if (0 == $count) {
            return ["count" => 0, "list" => []];
        }
        $sql .= " GROUP BY product_id";
        $sort_data = [
            'name',
            'price',
            'quantity',
            'status',
            'sort_order'
        ];
        if (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $filter_data['sort'];
        } else {
            $sql .= " ORDER BY name";
        }
        if (isset($filter_data['order']) && ($filter_data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        if (!empty($start) || !empty($limit)) {
            if ($start < 0) {
                $start = 0;
            }
            if ($limit < 1) {
                $limit = 20;
            }
            $sql .= " LIMIT " . (int)$start. "," . (int)$limit;
        }
        $list = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($list as $info) {
            $special = false;
            $product_specials = $this->getProductSpecials($appid, $info['product_id']);
            foreach ($product_specials  as $product_special) {
                if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time())
                    && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
                    $special = $product_special['price'];
                    break;
                }
            }
            $products[] = [
                'product_id' => $info['product_id'],
                'image'      => $info['image'],
                'name'       => $info['name'],
                'price'      => $info['price'],
                'special'    => $special,
                'quantity'   => $info['quantity'],
                'status'     => $info['status']
            ];
        }
        return ['count' => $count, 'list' => $products];
    }

    /**
     * 根据属性id获得商品的数量
     * @param $option_id
     */
    public function getTotalProductsByOptionId($appid, $option_id)
    {
        return $this->db->count('mcc_product_option', [
            'AND'   => [
                'appid'     => (int)$appid,
                'option_id' => (int)$option_id,
            ]
        ]);
    }

    /**
     * 获得优惠价格
     * @param $appid
     * @param $product_id
     * @return mixed
     */
    public function getProductSpecials($appid, $product_id)
    {
        return $this->db->select('mcc_product_special', '*', [
            'AND'   => [
                'appid'         => (int)$appid,
                'product_id'    => (int)$product_id,
            ]
        ]);
    }

    /**
     * @param $appid
     * @param $product_id
     * @return mixed
     */
    public function getStockStatus()
    {
        return $this->db->select('mcc_stock_status', '*');
    }

    /**
     * 获得商品选项
     * @param $product_id
     * @return array
     */
    public function getProductOptions($appid, $product_id)
    {
        $product_option_data = [];
        $sql = "SELECT * FROM `mcc_product_option` po
          LEFT JOIN `mcc_option` o ON (po.option_id = o.option_id AND o.appid = " .  $appid . ") 
          WHERE po.product_id = " . (int)$product_id . " AND po.appid = " . (int)$appid;
        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $product_option) {
            $product_option_value_data = [];
            $sql2 = "SELECT * FROM mcc_product_option_value WHERE product_option_id = " .
                (int)$product_option['product_option_id'] . ' AND appid = ' . $appid;
            $rows2 = $this->db->query($sql2)->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows2 as $product_option_value) {
                $product_option_value_data[] = [
                    'product_option_value_id' => $product_option_value['product_option_value_id'],
                    'option_value_id'         => $product_option_value['option_value_id'],
                    'quantity'                => $product_option_value['quantity'],
                    'subtract'                => $product_option_value['subtract'],
                    'price'                   => $product_option_value['price'],
                    'price_prefix'            => $product_option_value['price_prefix'],
                    'points'                  => $product_option_value['points'],
                    'points_prefix'           => $product_option_value['points_prefix'],
                ];
            }
            $product_option_data[] = [
                'product_option_id'    => $product_option['product_option_id'],
                'product_option_value' => $product_option_value_data,
                'option_id'            => $product_option['option_id'],
                'name'                 => $product_option['name'],
                'type'                 => $product_option['type'],
                'value'                => $product_option['value'],
                'required'             => $product_option['required']
            ];
        }
        return $product_option_data;
    }

    /**
     * 获得商品选项值
     * @param $product_id
     * @param $product_option_id
     * @return mixed
     */
    public function getProductOption($appid, $product_id, $product_option_id)
    {
        return $this->db->select('mcc_product_option', [
            '[>]mcc_option' => ['option_id' => 'option_id']
        ], [
            'mcc_product_option.product_option_id',
            'mcc_product_option.product_id',
            'mcc_product_option.option_id',
            'mcc_product_option.value',
            'mcc_product_option.required',
            'mcc_option.name',
            'mcc_option.type',
            'mcc_option.sort_order'
        ], [
            'AND' => [
                'mcc_product_option.appid'              => (int)$appid,
                'mcc_product_option.product_option_id'  => (int)$product_option_id,
                'mcc_product_option.product_id'         => (int)$product_id,
            ]
        ]);
    }

    /**
     * 获得商品选项和选项内容
     * @param $product_option_value_id
     * @param $product_option_id
     * @return mixed
     */
    public function getProductOptionValue($appid, $product_option_value_id, $product_option_id)
    {
        return $this->db->select('mcc_product_option_value', [
            '[>]mcc_option_value' => ["option_value_id" => "option_value_id"]
        ],[
            "mcc_product_option_value.product_option_value_id",
            "mcc_product_option_value.product_option_id",
            "mcc_product_option_value.product_id",
            "mcc_product_option_value.option_id",
            "mcc_product_option_value.option_value_id",
            "mcc_product_option_value.quantity",
            "mcc_product_option_value.subtract",
            "mcc_product_option_value.price",
            "mcc_product_option_value.price_prefix",
            "mcc_product_option_value.points",
            "mcc_product_option_value.points_prefix",
            "mcc_option_value.image",
            "mcc_option_value.name",
        ], [
            'AND' => [
                'mcc_option_value.appid'    => (int)$appid,
                'product_option_value_id'   => (int)$product_option_value_id,
                'product_option_id'         => (int)$product_option_id,
            ]
        ]);
    }

    /**
     * 获得选项值信息
     * @param $appid
     * @param $product_option_value_id
     * @return mixed
     */
    public function getProductOptionValueByProductOptionValueId($appid, $product_option_value_id)
    {
        return $this->db->get('mcc_product_option_value', '*', [
            'AND' => [
                'appid'                     => (int)$appid,
                'product_option_value_id'   => (int)$product_option_value_id,
            ]
        ]);
    }

    /**
     * 获得商品对应折扣
     * @param $appid
     * @param $product_id
     * @return mixed
     */
    public function getProductDiscounts($appid, $product_id)
    {
        return $this->db->select('mcc_product_discount', '*', [
            'AND' => [
                'product_id'    => (int)$product_id,
                'appid'         => (int)$appid
            ],
            'ORDER' => [
                'quantity'  => 'ASC',
                'priority'  => 'ASC',
                'price'  => 'ASC',
            ]
        ]);
    }

    /**
     * 获得商品对应 图片
     * @param $appid
     * @param $product_id
     * @return mixed
     */
    public function getProductImages($appid, $product_id)
    {
        return $this->db->select('mcc_product_image', '*', [
            'AND' => [
                'product_id'    => (int)$product_id,
                'appid'         => (int)$appid
            ]
        ]);
    }

    /**
     * 获得商品 对应 积分奖励
     * @param $appid
     * @param $product_id
     * @return array
     */
    public function getProductRewards($appid, $product_id)
    {
        $product_reward_data = [];
        $rows = $this->db->select('mcc_product_reward', '*', [
            'AND' => [
                'product_id'    => (int)$product_id,
                'appid'         => (int)$appid
            ]
        ]);
        foreach ($rows as $result) {
            $product_reward_data[$result['customer_group_id']] = ['points' => $result['points']];
        }
        return $product_reward_data;
    }


    /**
     * 按用户等级 获得 商店 的 商品列表
     * @param $customer_group_id
     * @param array $data
     * @return array
     */
    public function getProductsByCustomerGroupId($appid, $store_id, $customer_group_id, $data = [])
    {
        $sql = "SELECT p.product_id,p.price,p.quantity,
        (SELECT price FROM mcc_product_discount pd
            WHERE pd.product_id = p.product_id
            AND pd.customer_group_id = " . (int)$customer_group_id. " AND pd.quantity = 1
            AND ((pd.date_start = '0000-00-00' OR pd.date_start < NOW())
            AND (pd.date_end = '0000-00-00' OR pd.date_end > NOW())) AND pd.appid = " . (int)$appid ."
            ORDER BY pd.priority ASC, pd.price ASC LIMIT 1)
            AS discount,
		(SELECT price FROM mcc_product_special ps
            WHERE ps.product_id = p.product_id
            AND ps.customer_group_id = " . (int)$customer_group_id . " AND ((ps.date_start = '0000-00-00'
            OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) AND ps.appid = " . (int)$appid ."
            ORDER BY ps.priority ASC, ps.price ASC LIMIT 1)
            AS special";

        $sql_count = "SELECT count(1)";
        $sql_ext = "";
        if (!empty($data['filter_category_id'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql_ext .= " FROM mcc_category_path cp
                    LEFT JOIN mcc_product_to_category p2c ON (cp.category_id = p2c.category_id)";
            } else {
                $sql_ext .= " FROM mcc_product_to_category p2c";
            }
            if (!empty($data['filter_filter'])) {
                $sql_ext .= " LEFT JOIN mcc_product_filter pf ON (p2c.product_id = pf.product_id)
                    LEFT JOIN mcc_product p ON (pf.product_id = p.product_id)";
            } else {
                $sql_ext .= " LEFT JOIN mcc_product p ON (p2c.product_id = p.product_id)";
            }
        } else {
            $sql_ext .= " FROM mcc_product p";
        }
        $sql_ext .= " LEFT JOIN mcc_product_to_store p2s ON (p.product_id = p2s.product_id)
            WHERE p.status = 1 AND p.date_available <= NOW() AND quantity > 0
            AND p2s.store_id = " . (int)$store_id . " AND p.appid = " . (int)$appid;
        if (!empty($data['filter_category_id'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql_ext .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
            } else {
                $sql_ext .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
            }

            if (!empty($data['filter_filter'])) {
                $implode = [];
                $filters = explode(',', $data['filter_filter']);
                foreach ($filters as $filter_id) {
                    $implode[] = (int)$filter_id;
                }
                $sql_ext .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
            }
        }
        if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
            $sql_ext .= " AND (";
            if (!empty($data['filter_name'])) {
                $implode = [];
                $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));
                foreach ($words as $word) {
                    $implode[] = "p.name LIKE '%" . $this->escape($word) . "%'";
                }
                if ($implode) {
                    $sql_ext .= " " . implode(" AND ", $implode) . "";
                }
                if (!empty($data['filter_description'])) {
                    $sql_ext .= " OR p.description LIKE '%" . $this->escape($data['filter_name']) . "%'";
                }
            }
            if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
                $sql_ext .= " OR ";
            }
            if (!empty($data['filter_tag'])) {
                $sql_ext .= "p.tag LIKE '%" . $this->escape($data['filter_tag']) . "%'";
            }
            $sql_ext .= ")";
        }
        $sql_ext .= " GROUP BY p.product_id";
        $count = $this->db->query($sql_count . $sql_ext)
            ->fetchColumn();
        if (0 == $count) {
            return ["count" => 0, "list" => []];
        }
        $sort_data = [
            'p.name',
            'p.quantity',
            'p.price',
            'p.sort_order',
            'p.date_added'
        ];
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'p.name') {
                $sql_ext .= " ORDER BY LCASE(" . $data['sort'] . ")";
            } elseif ($data['sort'] == 'p.price') {
                $sql_ext .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special
                    WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
            } else {
                $sql_ext .= " ORDER BY " . $data['sort'];
            }
        } else {
            $sql_ext .= " ORDER BY p.sort_order";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql_ext .= " DESC, LCASE(p.name) DESC";
        } else {
            $sql_ext .= " ASC, LCASE(p.name) ASC";
        }
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql_ext .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        $rows = $this->db->query($sql . $sql_ext)->fetchAll();
        foreach ($rows as $row) {
            $product = $this->getProductInfo($appid, $row['product_id']);
            $product_data[] = [
                'product_id'       => $row['product_id'],
                'tag'              => $product['tag'],
                'name'             => $product['name'],
                'meta_title'       => $product['meta_title'],
                'meta_keyword'     => $product['meta_keyword'],
                'sku'              => $product['sku'],
                'quantity'         => $product['quantity'],
                'stock_status_id'  => $product['stock_status_id'],
                'image'            => $product['image'],
                'price'            => !empty($row['discount']) ? $row['discount']  : $product['price'],
                'special'          => (float) $row['special'],
                'points'           => $product['points'],
                'minimum'          => $product['minimum'],
                'subtract'         => $product['subtract'],
                'date_available'   => $product['date_available'],
                'status'           => $product['status'],
                'sort_order'       => $product['sort_order'],
            ];
        }
        return ['count' => $count, 'list' => $product_data];
    }



    /**
     * 按用户等级 获得 商店 获得详细商品信息
     * @param $product_id
     * @param $customer_group_id
     * @return array|bool
     */
    public function getProductDetailByCustomerGroupId($appid, $store_id, $product_id, $customer_group_id, $quantity = 1)
    {
        $sql = "SELECT DISTINCT *,
        mf.name as manufacturer_name,
        p.name AS name,
        p.image,
        (SELECT price FROM mcc_product_discount pd WHERE pd.product_id = p.product_id AND pd.customer_group_id = '" .
            (int)$customer_group_id . "' AND pd.quantity <= '" . $quantity . "' AND ((pd.date_start = '0000-00-00' OR pd.date_start < NOW())
            AND (pd.date_end = '0000-00-00' OR pd.date_end > NOW())) AND pd.appid = " . (int)$appid ."
            ORDER BY pd.priority ASC, pd.price ASC LIMIT 1
        ) AS discount,
        (SELECT price FROM mcc_product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '"
            . (int)$customer_group_id . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW())
            AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1
        ) AS special,
		(SELECT points FROM mcc_product_reward pr WHERE pr.product_id = p.product_id AND customer_group_id = '" . (int)$customer_group_id . "'
		    AND pr.appid = " . (int)$appid . "
		    ) AS reward,
		(SELECT ss.name FROM mcc_stock_status ss WHERE ss.stock_status_id = p.stock_status_id) AS stock_status,
		p.sort_order 
		FROM mcc_product p
		LEFT JOIN mcc_product_to_store p2s ON (p.product_id = p2s.product_id AND p2s.appid = " . (int)$appid . ")
        LEFT JOIN mcc_manufacturer mf ON (mf.manufacturer_id = p.manufacturer_id AND mf.appid = " . (int)$appid . ")
		WHERE p.product_id = " . (int)$product_id . " AND p.status = '1' AND p.date_available <= NOW()
		AND p2s.store_id = " . (int)$store_id . " AND p.appid = " . (int)$appid;
        $info = $this->db->query($sql)->fetch();
        if ($info) {
            return [
                'manufacturer_id'   => $info['manufacturer_id'],
                'manufacturer_name' => $info['manufacturer_name'],
                'product_id'        => $info['product_id'],
                'name'              => $info['name'],
                'description'       => $info['description'],
                'meta_title'        => $info['meta_title'],
                'meta_description'  => $info['meta_description'],
                'meta_keyword'      => $info['meta_keyword'],
                'tag'               => $info['tag'],
                'sku'               => $info['sku'],
                'quantity'          => $info['quantity'],
                'stock_status'      => $info['stock_status'],
                'image'             => $info['image'],
                'price'             => ($info['discount'] ? $info['discount'] : $info['price']),
                'special'           => $info['special'],
                'reward'            => $info['reward'],
                'points'            => $info['points'],
                'date_available'    => $info['date_available'],
                'subtract'          => $info['subtract'],
                'minimum'           => $info['minimum'],
                'sort_order'        => $info['sort_order'],
                'status'            => $info['status'],
                'date_added'        => $info['date_added'],
                'date_modified'     => $info['date_modified']
            ];
        } else {
            return false;
        }
    }

    /**
     * 更新库存
     * @param $product_id
     * @param $num
     * @param string $ext
     * @return mixed
     */
    public function tallyQuantity($appid, $product_id, $num, $ext = "-")
    {
        return $this->db->update("mcc_product", [
           "quantity[" . $ext . "]" => (int)$num
        ], [
           "AND" => [
               'appid'         => (int)$appid,
               "product_id"    => (int)$product_id,
               "subtract"      => 1
           ]
        ]);
    }
}