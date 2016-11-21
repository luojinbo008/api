<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-8-21
 * Time: 下午6:26
 */

namespace Lib\Model\Customer;

use Lib\Model\BaseModel;

class CustomerModel extends BaseModel
{
    /**
     * 商店获得用户基本信息
     * @param $appid
     * @param $store_id
     * @param $type
     * @param $value
     * @return mixed
     */
    public function getStoreCustomerInfo($appid, $store_id, $type, $value)
    {
        switch ($type) {
            case 'open_id' :
                $where = [
                    'AND'   => [
                        'mcc_customer.store_id'      => (int)$store_id,
                        'mcc_customer.appid'         => (int)$appid,
                        'mcc_customer.weixin_openid' => $value,
                    ]
                ];
                break;
            case 'customer_id' :
                $where = [
                    'AND'   => [
                        'mcc_customer.store_id'      => (int)$store_id,
                        'mcc_customer.appid'         => (int)$appid,
                        'mcc_customer.customer_id'   => (int)$value,
                    ]
                ];
                break;
            default:
                return false;
        }
        $info = $this->db->get('mcc_customer', [
            '[>]mcc_customer_group'   => ["customer_group_id" => "customer_group_id"],
        ],[
            'mcc_customer.customer_id',
            'mcc_customer.address_id',
            'mcc_customer.customer_group_id',
            'mcc_customer.fullname',
            'mcc_customer.nickname',
            'mcc_customer.idcard',
            'mcc_customer.telephone',
            'mcc_customer.status',
            'mcc_customer.date_added',
            'mcc_customer.weixin_openid',
            'mcc_customer_group.name(group_name)',
        ], $where);
        return $info;
    }

    /**
     * 获得会员基本信息
     * @param $appid
     * @param $customer_id
     * @return mixed
     */
    public function getCustomerInfo($appid, $customer_id)
    {
        $info = $this->db->get('mcc_customer', '*', [
            'AND'   => [
                'appid'         => $appid,
                'customer_id'   => $customer_id,
            ]
        ]);
       return $info;
    }

    /**
     * 获得客户列表
     * @param $appid
     * @param $filter_data
     * @param $start
     * @param $limit
     * @return mixed
     */
    public function getCustomerList($appid, $filter_data, $start, $limit)
    {
        $sql_count = " SELECT count(1) as count
          FROM mcc_customer c
          LEFT JOIN mcc_customer_group cg ON (c.customer_group_id = cg.customer_group_id) AND cg.appid =" . $appid;
        $sql = " SELECT c.*, cg.name AS customer_group
            FROM mcc_customer c
            LEFT JOIN mcc_customer_group cg ON (c.customer_group_id = cg.customer_group_id) AND cg.appid=" . $appid;
        $implode = [];
        if (isset($filter_data['filter_name']) && !empty($filter_data['filter_name'])) {
           $implode[] = "c.fullname LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }
        if (isset($filter_data['filter_telephone']) && !empty($filter_data['filter_telephone'])) {
           $implode[] = "c.telephone LIKE '" . $this->db->escape($filter_data['filter_telephone']) . "%'";
        }
        if (isset($filter_data['filter_customer_group_id']) && !empty($filter_data['filter_customer_group_id'])) {
           $implode[] = "c.customer_group_id = '" . (int)$filter_data['filter_customer_group_id'] . "'";
        }
        if (isset($filter_data['filter_ip']) && !empty($filter_data['filter_ip'])) {
           $implode[] = "c.customer_id IN (SELECT customer_id FROM mcc_customer_ip WHERE ip = '" . $this->db->escape($filter_data['filter_ip']) . "')";
        }
        if (isset($filter_data['filter_status']) && !is_null($filter_data['filter_status'])) {
           $implode[] = "c.status = '" . (int)$filter_data['filter_status'] . "'";
        }
        if (isset($filter_data['filter_date_added']) && !empty($filter_data['filter_date_added'])) {
           $implode[] = "DATE(c.date_added) = DATE('" . $this->db->escape($filter_data['filter_date_added']) . "')";
        }
        $implode[] = "c.appid = " . $appid;
        if ($implode) {
           $sql .= " WHERE " . implode(" AND ", $implode);
           $sql_count .= " WHERE " . implode(" AND ", $implode);
        }

        $count = $this->db->query($sql_count)
              ->fetchColumn();
        if (0 == $count) {
            return ["count" => 0, "list" => []];
        }

        $sql .= " ORDER BY name ASC, customer_id DESC";
        if (!empty($start) || !empty($limit)) {
           if ($start < 0) {
               $start = 0;
           }
           if ($limit < 1) {
               $limit = 20;
           }
           $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }
        $list = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return ["count" => $count, "list" => $list];
    }

    /**
     * 更新用户
     * @param $appid
     * @param $customer_id
     * @param null $customer_group_id
     * @param null $status
     * @return bool
     */
    public function updateCustomerInfo($appid, $customer_id, $customer_group_id = null, $status = null,
                                       $fullname = null, $nickname = null, $telephone = null, $idcard = null)
    {
        $data = [];
        if (null !== $status) {
            $data['status'] = (int)$status;
        }
        if (null !== $customer_group_id) {
            $data['customer_group_id'] = (int)$customer_group_id;
        }
        if (null !== $fullname) {
            $data['fullname'] = $fullname;
        }
        if (null !== $nickname) {
            $data['nickname'] = $nickname;
        }
        if (null !== $telephone) {
            $data['telephone'] = $telephone;
        }
        if (null !== $idcard) {
            $data['idcard'] = $idcard;
        }
        if (empty($data)) {
            return false;
        }
        $this->db->update('mcc_customer', $data, [
            'AND'   => [
                'appid'         => (int)$appid,
                'customer_id'   => (int)$customer_id,
            ]
        ]);
        return true;
    }

    /**
     * 统计用户信息
     * @param $appid
     * @param array $filter_data
     * @return mixed
     */
    public function getTotalCustomers($appid, $filter_data = [])
    {
        $sql = "SELECT COUNT(*) AS total FROM mcc_customer WHERE appid = " . (int)$appid;
        $implode = [];
        if (!empty($filter_data['filter_name'])) {
            $implode[] = "fullname LIKE '%" . $filter_data['filter_name'] . "%'";
        }
        if (!empty($filter_data['filter_customer_group_id'])) {
            $implode[] = "customer_group_id = '" . (int)$filter_data['filter_customer_group_id'] . "'";
        }
        if (!empty($filter_data['filter_ip'])) {
            $implode[] = "customer_id IN (SELECT customer_id FROM mcc_customer_ip WHERE ip = '" . $filter_data['filter_ip'] . "')";
        }
        if (isset($filter_data['filter_status']) && !is_null($filter_data['filter_status'])) {
            $implode[] = "status = '" . (int)$filter_data['filter_status'] . "'";
        }
        if (!empty($filter_data['filter_date_added'])) {
            $implode[] = "DATE(date_added) = DATE('" . $filter_data['filter_date_added'] . "')";
        }
        if (!empty($filter_data['filter_telephone'])) {
            $implode[] = "filter_telephone = " . $filter_data['filter_telephone'];
        }
        if ($implode) {
            $sql .= " AND " . implode(" AND ", $implode);
        }
        return $this->db->query($sql)->fetchColumn();
    }

    /**
     * 获得当前在线用户
     * @param $appid
     * @param array $filter_data
     * @return mixed
     */
    public function getTotalCustomersOnline($appid, $filter_data = [])
    {
        $sql = "SELECT COUNT(*) AS total FROM `mcc_customer_online` co
            LEFT JOIN mcc_customer c ON (co.customer_id = c.customer_id AND c.appid = " . (int)$appid . ")
            WHERE co.appid = " . (int)$appid;
        $implode = [];
        if (!empty($filter_data['filter_ip'])) {
            $implode[] = "co.ip LIKE '" . $filter_data['filter_ip'] . "'";
        }
        if (!empty($filter_data['filter_customer'])) {
            $implode[] = "co.customer_id > 0 AND c.fullname LIKE '" . $filter_data['filter_customer'] . "'";
        }
        if ($implode) {
            $sql .= " AND " . implode(" AND ", $implode);
        }
        return $this->db->query($sql)->fetchColumn();
    }


    /**
     * 统计本日客户
     * @param $appid
     * @return array
     */
    public function getTotalCustomersByDay($appid)
    {
        $customer_data = [];
        for ($i = 0; $i < 24; $i++) {
            $customer_data[$i] = [
                'hour'  => $i,
                'total' => 0
            ];
        }
        $sql = "SELECT COUNT(*) AS total, HOUR(date_added) AS hour
            FROM mcc_customer
            WHERE DATE(date_added) = DATE(NOW()) AND appid = " . (int)$appid . "
            GROUP BY HOUR(date_added) ORDER BY date_added ASC";
        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $result) {
            $customer_data[$result['hour']] = [
                'hour'  => $result['hour'],
                'total' => $result['total']
            ];
        }
        return $customer_data;
    }

    /**
     * 获得本周客户
     * @param $appid
     * @return array
     */
    public function getTotalCustomersByWeek($appid)
    {
        $customer_data = [];
        $date_start = strtotime('-' . date('w') . ' days');
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', $date_start + ($i * 86400));
            $customer_data[date('w', strtotime($date))] = [
                'day'   => date('D', strtotime($date)),
                'total' => 0
            ];
        }
        $sql = "SELECT COUNT(*) AS total, date_added
          FROM mcc_customer
          WHERE DATE(date_added) >= DATE('" . date('Y-m-d', $date_start) . "') 
          AND appid = " . (int)$appid . "
          GROUP BY DAYNAME(date_added)";
        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $result) {
            $customer_data[date('w', strtotime($result['date_added']))] = [
                'day'   => date('D', strtotime($result['date_added'])),
                'total' => $result['total']
            ];
        }
        return $customer_data;
    }

    /**
     * 统计本月客户
     * @param $appid
     * @return array
     */
    public function getTotalCustomersByMonth($appid)
    {
        $customer_data = [];
        for ($i = 1; $i <= date('t'); $i++) {
            $date = date('Y') . '-' . date('m') . '-' . $i;
            $customer_data[date('j', strtotime($date))] = [
                'day'   => date('d', strtotime($date)),
                'total' => 0
            ];
        }
        $sql = "SELECT COUNT(*) AS total, date_added
            FROM mcc_customer
            WHERE DATE(date_added) >= '" . date('Y') . '-' . date('m') . '-1' . "'
            AND appid = " . (int)$appid . "
            GROUP BY DATE(date_added)";
        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $result) {
            $customer_data[date('j', strtotime($result['date_added']))] = [
                'day'   => date('d', strtotime($result['date_added'])),
                'total' => $result['total']
            ];
        }
        return $customer_data;
    }

    /**
     * 统计本年客户
     * @param $appid
     * @return array
     */
    public function getTotalCustomersByYear($appid)
    {
        $customer_data = array();
        for ($i = 1; $i <= 12; $i++) {
            $customer_data[$i] = [
                'month' => date('M', mktime(0, 0, 0, $i)),
                'total' => 0
            ];
        }
        $sql = "SELECT COUNT(*) AS total, date_added
            FROM mcc_customer
            WHERE YEAR(date_added) = YEAR(NOW()) 
            AND appid = " . (int)$appid . "
            GROUP BY MONTH(date_added)";
        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $result) {
            $customer_data[date('n', strtotime($result['date_added']))] = [
                'month' => date('M', strtotime($result['date_added'])),
                'total' => $result['total']
            ];
        }
        return $customer_data;
    }

    /**
     * 获得用户积分
     * @param $appid
     * @param $customer_id
     * @return mixed
     */
    public function getRewardTotal($appid, $customer_id)
    {
        return $this->db->sum('mcc_customer_reward', 'points', [
            'AND' => [
                'appid'         => (int)$appid,
                'customer_id'   => (int)$customer_id
            ]
        ]);
    }

    /**
     * 根据手机号 获得账号数量
     * @param $telephone
     * @return mixed
     */
    public function getTotalCustomersByTelephone($appid, $store_id, $telephone)
    {
        return $this->db->count("mcc_customer", [
            'AND' => [
                "appid"     => (int)$appid,
                "store_id"  => (int)$store_id,
                "telephone" => $this->escape($telephone),
            ]
        ]);
    }

    /**
     *
     * 新增用户（微信公众号端）
     */
    public function addWechatCustomer($appid, $store_id, $customer_group_id, $nickname,
                                            $fullname, $telephone, $ip, $idcard, $openId)
    {
        $last_customer_id = $this->db->insert("mcc_customer", [
            'appid'             => (int)$appid,
            'store_id'          => (int)$store_id,
            'nickname'          => $this->escape($nickname),
            'customer_group_id' => (int)$customer_group_id,
            'fullname'          => $this->escape($fullname),
            'telephone'         => $this->escape($telephone),
            'ip'                => $this->escape($ip),
            'status'            => 1,
            'idcard'            => $this->escape($idcard),
            'weixin_openid'     => $this->escape($openId),
            'date_added'        => date("Y-m-d H:i:s", CURRENT_TIME),
        ]);
        return $last_customer_id;
    }

    /**
     * 设置用户默认地址
     * @param $appid
     * @param $customer_id
     * @param $address_id
     * @return mixed
     */
    public function updateCustomerDefaultAddress($appid, $customer_id, $address_id)
    {
        return $this->db->update('mcc_customer', [
            'address_id'    => (int)$address_id,
        ], [
            'AND'   => [
                'appid'         => (int)$appid,
                'customer_id'   => (int)$customer_id
            ]
        ]);
    }

    /**
     * 增加积分奖励
     * @param $customer_id
     * @param $order_id
     * @param $points
     * @param string $description
     * @return mixed
     */
    public function addReward($appid, $customer_id, $order_id, $description = '')
    {
        $points = $this->db->sum("mcc_order_product", "reward", [
            'AND'   => [
                'appid'     => (int)$appid,
                "order_id"  => (int)$order_id,
            ]
        ]);
        if($points > 0){
            $this->db->insert('mcc_customer_reward', [
                'appid'         => (int)$appid,
                'customer_id'   => $customer_id,
                'order_id'      => $order_id,
                'description'   => $description,
                'points'        => $points,
                'date_added'    => date('Y-m-d H:i:s')
            ]);
        }
    }
} 