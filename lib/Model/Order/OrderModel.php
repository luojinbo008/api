<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/8/25
 * Time: 15:05
 */

namespace Lib\Model\Order;
use Lib\Model\BaseModel;
use Lib\Model\Product\ProductModel;
use Lib\Model\Product\OptionModel;

class OrderModel extends BaseModel
{
    public $implode_status_pay = [
        ORDER_STATUS_END,
        ORDER_STATUS_REFUND_START,
        ORDER_STATUS_COMMENT
    ];

    /**
     * 订单记录日志
     * @param $order_id
     * @param $order_status
     * @param string $comment
     * @param bool $notify
     * @param bool $override
     */
    public function addOrderHistory($appid, $order_id, $order_status, $comment = '', $notify = false)
    {
        $order_info = $this->getOrderInfo($appid, $order_id);
        if ($order_info) {
            if ($order_status == ORDER_STATUS_START) {
                $order_products = $this->getProductByOrderId($appid, $order_id);
                $productModel = new ProductModel();
                $optionModel = new OptionModel();
                // 扣库存
                foreach ($order_products as $order_product) {
                    $productInfo = $productModel->getProductInfo($appid, $order_product['product_id']);
                    if ($productInfo && 1 == $productInfo['subtract']) {
                        $productModel->tallyQuantity($appid, $order_product['product_id'], $order_product['quantity'], '-');
                    }
                    $options = $this->getOptionsByOrderIdProductId($appid, $order_id, $order_product['order_product_id']);
                    foreach ($options as $option) {
                        $productOptionValue = $productModel->getProductOptionValueByProductOptionValueId($appid, $option['product_option_value_id']);
                        if($productOptionValue && 1 == $productOptionValue['subtract']) {
                            $optionModel->tallyQuantity($appid, $option['product_option_value_id'], $order_product['quantity'], '-');
                        }
                    }
                }
            }

            // 更新订单状态
            $this->updateOrderStatus($appid, $order_id, $order_status, $comment);

            // 插入日志
            $this->db->insert('mcc_order_history',[
                'appid'             => (int)$appid,
                'order_id'          => (int)$order_id,
                'order_status_id'   => (int)$order_status,
                'notify'            => (int)$notify,
                'comment'           => $this->escape($comment),
                'date_added'        => date('Y-m-d H:i:s', CURRENT_TIME)
            ]);

            // 返还库存
            if (in_array($order_status, [ORDER_STATUS_CANCEL, ORDER_STATUS_CLOSE, ORDER_STATUS_REFUND_END])) {
                $query = $this->getProductByOrderId($appid, $order_id);
                $productModel = new ProductModel();
                $optionModel = new OptionModel();
                foreach ($query as $order_product) {
                    $productModel = new ProductModel();
                    $productInfo = $productModel->getProductInfo($appid, $order_product['product_id']);
                    if ($productInfo && 1 == $productInfo['subtract']) {
                        $productModel->tallyQuantity($appid, $order_product['product_id'], $order_product['quantity'], '+');
                    }
                    $options = $this->getOptionsByOrderIdProductId($appid, $order_id, $order_product['order_product_id']);
                    foreach ($options as $option) {
                        $productOptionValue = $productModel->getProductOptionValueByProductOptionValueId($appid, $option['product_option_value_id']);
                        if($productOptionValue && 1 == $productOptionValue['subtract']) {
                            $optionModel->tallyQuantity($appid, $option['product_option_value_id'], $order_product['quantity'], '+');
                        }
                    }
                }
            }
        }
    }

    /**
     * 更新订单状态
     * @param $appid
     * @param $order_id
     * @param $status
     * @return mixed
     */
    public function updateOrderStatus($appid, $order_id, $status, $comment = '')
    {
        return $this->db->update("mcc_order", [
            "order_status_id"   => (int)$status,
            "comment"           =>  $this->escape($comment),
            "date_modified"     => date('Y-m-d H:i:s', CURRENT_TIME)
        ], [
            "AND" => [
                "appid"     => (int)$appid,
                "order_id"  => (int)$order_id
            ]
        ]);
    }

    /**
     * 获得订单商品选项
     * @param $order_id
     * @param $order_product_id
     * @return mixed
     */
    public function getOptionsByOrderIdProductId($appid, $order_id, $order_product_id)
    {
       return $this->db->select("mcc_order_option", "*", [
           'AND' => [
               "appid"              => (int)$appid,
               "order_id"           => (int)$order_id,
               "order_product_id"   => (int)$order_product_id,
           ]
       ]);
    }

    /**
    * 获得订单商品
    * @param $order_id
    * @return mixed
    */
    public function getProductByOrderId($appid, $order_id)
    {
         return $this->db->select("mcc_order_product", "*", [
             'AND' => [
                 "appid"    => (int)$appid,
                 "order_id" => (int)$order_id
             ]
         ]);
    }

    /**
     * 生成订单
     * @param $appid
     * @param $store_id
     * @param $customerInfo
     * @param $products
     * @param $total
     * @param $ip
     * @param string $comment
     * @return int
     */
    public function addOrder($appid, $store_id, $customerInfo, $products, $total, $ip, $comment = '')
    {
        $db = $this->db;
        $order_id = 0;
        $db->action(function($db) use ($appid, $store_id, $customerInfo, $products, $total, $ip, $comment, &$order_id) {
            $last_order_id = $db->insert('mcc_order', [
                'store_id'          => (int)$store_id,
                'appid'             => (int)$appid,
                'customer_id'       => (int)$customerInfo['customer_id'],
                'customer_group_id' => (int)$customerInfo['customer_group_id'],
                'fullname'          => $this->escape($customerInfo['fullname']),
                'telephone'         => $this->escape($customerInfo['telephone']),
                'comment'           => $this->escape($comment),
                'total'             => (float)$total,
                'ip'                => $ip,
                'order_status_id'   => ORDER_STATUS_START,
                'date_added'        => date("Y-m-d H:i:s", CURRENT_TIME),
                'date_modified'     => date("Y-m-d H:i:s", CURRENT_TIME),
            ]);
            if (!$last_order_id) {
                return false;
            }
            if (!empty($products)) {
                foreach ($products as $product) {
                    $order_product_id = $this->db->insert('mcc_order_product', [
                        'appid'         => (int)$appid,
                        'order_id'      => (int)$last_order_id,
                        'product_id'    => (int)$product['product_id'],
                        'name'          => $this->escape($product['name']),
                        'quantity'      => (int)$product['quantity'],
                        'price'         => (float)$product['price'],
                        'total'         => (float)$product['total'],
                        'reward'        => (int)$product['reward'],
                        'shipping'      => (int)$product['shipping'],
                        'image'         => $this->escape($product['image']),
                    ]);

                    foreach ($product['option'] as $option) {
                        $this->db->insert('mcc_order_option', [
                            'appid'                     => (int)$appid,
                            'order_id'                  => (int)$last_order_id,
                            'order_product_id'          => (int)$order_product_id,
                            'product_option_id'         => (int)$option['product_option_id'],
                            'product_option_value_id'   => (int)$option['product_option_value_id'],
                            'name'                      => $this->escape($option['name']),
                            'value'                     => $this->escape($option['value']),
                            'type'                      => $this->escape($option['type']),
                        ]);
                    }
                }
            }
            $order_id = $last_order_id;
            return true;
        });
   		return $order_id;
   	}

    /**
     * 用户获得我的订单列表
     * @param $appid
     * @param $store_id
     * @param $customer_id
     * @param $status
     * @param $start
     * @param $limit
     * @return mixed
     */
    public function getOrderListByCustomerId($appid, $store_id, $customer_id, $status, $start, $limit)
    {
        $count = $this->db->count('mcc_order', [
            'AND' => [
                'appid'                 => (int)$appid,
                'store_id'              => (int)$store_id,
                'customer_id'           => (int)$customer_id,
                'order_status_id[!]'    => [ORDER_STATUS_CANCEL, ORDER_STATUS_CLOSE],
                'order_status_id'       => $status
            ]
        ]);
        if ($count == 0) {
            return ['count' => 0, 'list' => []];
        }
        $orderList = $this->db->select('mcc_order', '*', [
            'AND' => [
                'appid'                 => (int)$appid,
                'store_id'              => (int)$store_id,
                'customer_id'           => (int)$customer_id,
                'order_status_id[!]'    => [ORDER_STATUS_CANCEL, ORDER_STATUS_CLOSE],
                'order_status_id'       => $status
            ],
            "ORDER" => ['order_id' => 'DESC'],
                "LIMIT" => [$start, $limit]
        ]);
        foreach($orderList as &$info) {
            $product_list = $this->getOrderProducts($appid, $info['order_id']);
            $info['products'] = $product_list;
        }
        unset($info);
        return ['count' => $count, 'list' => $orderList];;
    }

    /**
     * 获得订单列表
     * @param array $data
     * @return mixed
     */
    public function getOrderList($appid, $filter_data = [])
    {
        $sql = "SELECT order_id, fullname AS customer, total, date_added, date_modified, order_status_id
                  FROM mcc_order WHERE appid = " . $appid;
        $sql_count = "SELECT count(1) FROM mcc_order WHERE appid = " . $appid;
        $implode = [];
        if (isset($filter_data['filter_order_status'])) {
            $implode_status = [];
            $order_statuses = explode(',', $filter_data['filter_order_status']);
            foreach ($order_statuses as $order_status_id) {
                $implode_status[] = "order_status_id = '" . (int)$order_status_id . "'";
            }
            if ($implode_status) {
                $implode[] = "(" . implode(" OR ", $implode_status) . ")";
            }
        } else {
            $implode[] = "order_status_id > '0'";
        }
        if (!empty($filter_data['filter_order_id'])) {
            $implode[] = "order_id = '" . (int)$filter_data['filter_order_id'] . "'";
        }
        if (!empty($filter_data['filter_customer'])) {
            $implode[] = "fullname LIKE '%" . $filter_data['filter_customer'] . "%'";
        }
        if (!empty($filter_data['filter_date_added'])) {
            $implode[] = "DATE(date_added) = DATE('" . $filter_data['filter_date_added'] . "')";
        }
        if (!empty($filter_data['filter_date_modified'])) {
            $implode[] = "DATE(date_modified) = DATE('" . $filter_data['filter_date_modified'] . "')";
        }
        if (!empty($filter_data['filter_total'])) {
            $implode[] = "total = '" . (float)$filter_data['filter_total'] . "'";
        }
        if ($implode) {
            $sql_count .= ' AND ' . implode(' AND ', $implode);
            $sql .= ' AND '. implode(' AND ', $implode);
        }
        $count = $this->db->query($sql_count)
            ->fetchColumn();
        if (0 == $count) {
            return ['count' => 0, 'list' => []];
        }
        $sort_data = [
            'order_id',
            'customer',
            'status',
            'date_added',
            'date_modified',
            'total'
        ];
        if (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $filter_data['sort'];
        } else {
            $sql .= " ORDER BY order_id";
        }
        if (isset($filter_data['order']) && ($filter_data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
        }
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        $list = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return ['count' => $count, 'list' => $list];
    }

    /**
     * 获得订单基本信息
     * @param $appid
     * @param $order_id
     * @return mixed
     */
    public function getOrderInfo($appid, $order_id)
    {
        return $this->db->get('mcc_order', '*', [
            'AND'   => [
                'appid'     => (int)$appid,
                'order_id'  => (int)$order_id
            ]
        ]);
    }

    /**
     * 获得订单拥有商品（详细信息）
     * @param $appid
     * @param $order_id
     * @return array
     */
    public function getOrderProducts($appid, $order_id)
    {
        $rows = $this->db->select('mcc_order_product', [
            '[>]mcc_order_option'   => ["order_product_id" => "order_product_id"],
        ],[
            "mcc_order_product.order_product_id",
            "mcc_order_product.order_id",
            "mcc_order_product.product_id",
            "mcc_order_product.name",
            "mcc_order_product.quantity",
            "mcc_order_product.price",
            "mcc_order_product.total",
            "mcc_order_product.reward",
            "mcc_order_product.image",
            "mcc_order_product.shipping",
            "mcc_order_option.order_option_id",
            "mcc_order_option.product_option_id" ,
            "mcc_order_option.product_option_value_id(option_value_id)",
            "mcc_order_option.name(option_name)",
            "mcc_order_option.value(option_value)",
            "mcc_order_option.type(option_type)"
        ],[
            'AND' => [
                'mcc_order_product.order_id'    => (int)$order_id,
                'mcc_order_product.appid'       => (int)$appid,
            ],
        ]);
        $product_data = [];
        foreach($rows as $row) {
            $option_data = [];
            if(!empty($row['order_option_id'])) {
                $option_data = [
                    "order_option_id" => $row['order_option_id'],
                    "product_option_id" => $row['product_option_id'],
                    "product_option_value_id" => $row['option_value_id'],
                    "name" => $row['option_name'],
                    "value" => $row['option_value'],
                    "type" => $row['option_type'],
                ];
            }
            if (isset($product_data[$row['product_id']])) {
                $product_data[$row['product_id']]['option'][] = $option_data;
                $product_data[$row['product_id']]['quantity'] += $row['quantity'];
                $product_data[$row['product_id']]['total'] = sprintf("%.2f", $product_data[$row['product_id']]['total']
                    + $row['total']);
                $product_data[$row['product_id']]['price'] = $product_data[$row['product_id']]['price'] > $row['price'] ?
                    $product_data[$row['product_id']]['price'] : $row['price'];
            } else {
                $product_data[$row['product_id']] = [
                    'product_id'      => $row['product_id'],
                    'shipping'        => $row['shipping'],
                    'address'         => json_decode($row['address'], true),
                    'name'            => $row['name'],
                    'image'           => $row['image'],
                    'option'          => [$option_data],
                    'quantity'        => $row['quantity'],
                    'price'           => $row['price'],
                    'total'           => $row['total'],
                    'reward'          => $row['reward'],
                ];
            }
        }
        return $product_data;
    }

    /**
     * 获得订单历史操作列表
     * @param $appid
     * @param $order_id
     * @param array $filter_data
     * @return array
     */
    public function getOrderHistories($appid, $order_id, $filter_data = [])
    {
        $where['AND'] = [
            'order_id'  => (int)$order_id,
            'appid'     => (int)$appid
        ];
        $count = $this->db->count('mcc_order_history', $where);
        if (0 == $count) {
            return ['count' => 0, 'list' => []];
        }
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            $where['LIMIT'] = [$filter_data['start'], $filter_data['limit']];
        }
        $where['ORDER'] = [
            'order_history_id' => 'DESC'
        ];
        $list = $this->db->select('mcc_order_history', '*', $where);
        return ['count' => $count, 'list' => $list];
    }

    /**
     * 订单数量统计
     * @param $appid
     * @param array $filter_data
     * @return mixed
     */
    public function getTotalOrders($appid, $filter_data = [])
    {
        $sql = "SELECT COUNT(*) AS total FROM `mcc_order` WHERE appid = " . (int)$appid;
        $implode = [];
        if (isset($filter_data['filter_order_status'])) {
            $implode_status = [];
            foreach ($filter_data['filter_order_status'] as $order_status_id) {
                $implode_status[] = "order_status_id = '" . (int)$order_status_id . "'";
            }
            if ($implode_status) {
                $implode[] = "(" . implode(" OR ", $implode_status) . ")";
            }
        } else {
            $implode[] = "order_status_id in (" . implode(', ', $this->implode_status_pay) .  ")";
        }
        if (!empty($filter_data['filter_order_id'])) {
            $implode[] = "order_id = '" . (int)$filter_data['filter_order_id'] . "'";
        }
        if (!empty($filter_data['filter_customer'])) {
            $implode[]  = "fullname LIKE '%" . $filter_data['filter_customer'] . "%'";
        }
        if (!empty($filter_data['filter_date_added'])) {
            $implode[]  = "DATE(date_added) = DATE('" . $filter_data['filter_date_added'] . "')";
        }
        if (!empty($filter_data['filter_date_modified'])) {
            $implode[]  = "DATE(date_modified) = DATE('" . $filter_data['filter_date_modified'] . "')";
        }
        if (!empty($filter_data['filter_total'])) {
            $implode[] = "total = '" . (float)$filter_data['filter_total'] . "'";
        }
        if ($implode) {
            $sql .= ' AND ' . implode(' AND ', $implode);
        }
        return $this->db->query($sql)->fetchColumn();
    }

    /**
     * 统计销售额
     * @param $appid
     * @param array $filter_data
     * @return mixed
     */
    public function getTotalSales($appid, $filter_data = [])
    {
        $sql = "SELECT SUM(total) AS total FROM `mcc_order`  
            WHERE appid = " . (int)$appid . " AND order_status_id in (" . implode(', ', $this->implode_status_pay) .  ")";
        if (!empty($filter_data['filter_date_added'])) {
            $sql .= " AND DATE(date_added) = DATE('" . $filter_data['filter_date_added'] . "')";
        }
        return $this->db->query($sql)->fetchColumn();
    }

    /**
     * 获得日订单统计
     * @param $appid
     * @return array
     */
    public function getTotalOrdersByDay($appid)
    {
        $order_data = [];
        for ($i = 0; $i < 24; $i++) {
            $order_data[$i] = [
                'hour'  => $i,
                'total' => 0
            ];
        }
        $sql = "SELECT SUM(total)AS total, HOUR(date_added) AS hour
            FROM mcc_order
            WHERE order_status_id in (" . implode(', ', $this->implode_status_pay) . ") 
            AND DATE(date_added) = DATE(NOW()) AND appid = " . (int)$appid . "
            GROUP BY HOUR(date_added)
            ORDER BY date_added ASC";
        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $result) {
            $order_data[$result['hour']] = [
                'hour'  => $result['hour'],
                'total' => $result['total']
            ];
        }
        return $order_data;
    }

    /**
     * 获得周订单统计
     * @param $appid
     * @return array
     */
    public function getTotalOrdersByWeek($appid)
    {
        $order_data = [];
        $date_start = strtotime('-' . date('w') . ' days');
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', $date_start + ($i * 86400));
            $order_data[date('w', strtotime($date))] = [
                'day'   => date('D', strtotime($date)),
                'total' => 0
            ];
        }
        $sql = "SELECT SUM(total) AS total, date_added
            FROM mcc_order
            WHERE order_status_id in (" . implode(', ', $this->implode_status_pay) . ") 
            AND DATE(date_added) >= DATE('" . date('Y-m-d', $date_start) . "') 
            AND appid = " . (int)$appid . "
            GROUP BY DAYNAME(date_added)";
        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $result) {
            $order_data[date('w', strtotime($result['date_added']))] = [
                'day'   => date('D', strtotime($result['date_added'])),
                'total' => $result['total']
            ];
        }
        return $order_data;
    }

    /**
     * 获得月订单统计
     * @param $appid
     * @return array
     */
    public function getTotalOrdersByMonth($appid)
    {
        $order_data = [];
        for ($i = 1; $i <= date('t'); $i++) {
            $date = date('Y') . '-' . date('m') . '-' . $i;
            $order_data[date('j', strtotime($date))] = [
                'day'   => date('d', strtotime($date)),
                'total' => 0
            ];
        }
        $sql = "SELECT SUM(total) AS total, date_added FROM mcc_order
            WHERE order_status_id in (" . implode(', ', $this->implode_status_pay) . ") 
            AND DATE(date_added) >= '" . date('Y') . '-' . date('m') . '-1' . "' 
            AND appid = " . (int)$appid . "
            GROUP BY DATE(date_added)";
        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $result) {
            $order_data[date('j', strtotime($result['date_added']))] = [
                'day'   => date('d', strtotime($result['date_added'])),
                'total' => $result['total']
            ];
        }
        return $order_data;
    }

    /**
     * 获得年订单统计
     * @param $appid
     * @return array
     */
    public function getTotalOrdersByYear($appid)
    {
        $order_data = [];
        for ($i = 1; $i <= 12; $i++) {
            $order_data[$i] = [
                'month' => date('M', mktime(0, 0, 0, $i)),
                'total' => 0
            ];
        }
        $sql = "SELECT SUM(total) AS total, date_added
        FROM mcc_order 
        WHERE order_status_id in (" . implode(', ', $this->implode_status_pay) . ") 
        AND YEAR(date_added) = YEAR(NOW())
        AND appid = " . (int)$appid . "
        GROUP BY MONTH(date_added)";
        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $result) {
            $order_data[date('n', strtotime($result['date_added']))] = [
                'month' => date('M', strtotime($result['date_added'])),
                'total' => $result['total']
            ];
        }
        return $order_data;
    }

    /**
     * 获得订单发货地址列表
     * @param $appid
     * @param $order_id
     * @return mixed
     */
    public function getOrderAddressList($appid, $order_id)
    {
        return $this->db->select('mcc_order_address', '*', [
            'AND' => [
                'appid'     => $appid,
                'order_id'  => $order_id
            ]
        ]);
    }

    /**
     * 删除订单地址
     * @param $appid
     * @param $order_id
     */
    public function deleteOrderAddress($appid, $order_id)
    {
        $this->db->delete('mcc_order_address', [
            'AND' => [
                'appid'     => $appid,
                'order_id'  => $order_id
            ]
        ]);
    }

    /**
     * 新增订单地址
     * @param $appid
     * @param $info
     * @return mixed
     */
    public function addOrderAddress($appid, $info)
    {
        $data = [
            'appid'                     => $appid,
            'order_id'                  => $info['order_id'],
            'shipping_fullname'         => $info['shipping_fullname'],
            'shipping_company'          => $info['shipping_company'],
            'shipping_address'          => $info['shipping_address'],
            'shipping_city'             => $info['shipping_city'],
            'shipping_postcode'         => $info['shipping_postcode'],
            'shipping_country'          => $info['shipping_country'],
            'shipping_country_id'       => $info['shipping_country_id'],
            'shipping_zone'             => $info['shipping_zone'],
            'shipping_zone_id'          => $info['shipping_zone_id'],
            'shipping_address_format'   => $info['shipping_address_format'],
            'shipping_custom_field'     => $info['shipping_custom_field'],
            'shipping_method'           => $info['shipping_method'],
            'shipping_code'             => $info['shipping_code'],
            'shipping_telephone'        => $info['shipping_telephone'],
            'date_added'                => date('Y-m-d', CURRENT_TIME),
        ];
        return $this->db->insert('mcc_order_address', $data);
    }

    /**
     * 收单服务
     * @param $order_id
     * @param $transaction_id
     * @param $payment
     * @return mixed
     */
    public function acceptOrder($appid, $order_id, $transaction_id, $payment)
    {
        return $this->db->update("mcc_order", [
            "order_status_id"   => ORDER_STATUS_END,
            'transaction_id'    => $transaction_id,
            'payment'           => $payment,
            "date_modified"     => date('Y-m-d H:i:s', CURRENT_TIME)
        ], [
            "AND" => [
                'appid'     => (int)$appid,
                "order_id"  => (int)$order_id
            ]
        ]);
    }
}