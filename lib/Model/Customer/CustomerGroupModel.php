<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-8-21
 * Time: 下午6:26
 */

namespace Lib\Model\Customer;

use Lib\Model\BaseModel;

class CustomerGroupModel extends BaseModel
{
    /**
     * 获得会员类型列表
     * @param $appid
     * @param $filter_data
     * @return mixed
     */
    public function getCustomerGroups($appid, $filter_data, $start = 0, $limit = 0)
    {
        $sql_count = "SELECT count(1) as count FROM mcc_customer_group WHERE appid = " . $appid;
        $count = $this->db->query($sql_count)
            ->fetchColumn();
        if (0 == $count) {
            return ["count" => 0, "list" => []];
        }
        $sql = "SELECT * FROM mcc_customer_group WHERE appid = " . $appid;
        $sort_data = [
            'name',
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
            $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }
        $list =  $this->db->query($sql)->fetchAll();
        return ['count' => $count, 'list' => $list];
    }

    /**
     * 获得分组基本信息
     * @param $appid
     * @param $customer_group_id
     * @return mixed
     */
    public function getCustomerGroupInfo($appid, $customer_group_id)
    {
        return $this->db->get('mcc_customer_group', '*', [
            'AND' => [
                'appid'             => (int)$appid,
                'customer_group_id' => (int)$customer_group_id
            ]
        ]);
    }
} 