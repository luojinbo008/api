<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-13
 * Time: 下午3:49
 */

namespace Lib\Model\Product;

use Lib\Model\BaseModel;

class FilterModel extends BaseModel
{
    /**
     * 新增筛选列表
     * @param $appid
     * @param $name
     * @param $sort_order
     * @param $filters
     * @return int
     */
    public function addGroup($appid, $name, $sort_order, $filters)
    {
        $db = $this->db;
        $filter_group_id = 0;
        $db->action(function($db) use ($appid, $name, $sort_order, $filters, &$filter_group_id) {
            $last_group_id = $db->insert("mcc_filter_group", [
                'appid'             => (int)$appid,
                'name'              => $name,
                'sort_order'        => (int)$sort_order
            ]);
            if (!$last_group_id) {
                return false;
            }
            foreach ($filters as $filter) {
                $last_id = $db->insert("mcc_filter", [
                    'appid'             => (int)$appid,
                    'name'              => $filter['filter_name'],
                    'filter_group_id'   => (int)$last_group_id,
                    'sort_order'        => (int)$filter['sort_order']
                ]);
                if(!$last_id){
                    return false;
                }
            }
            $filter_group_id = $last_group_id;
            return true;
        });
        return $filter_group_id;
    }

    /**
     * 获得分组基本信息
     * @param $group_id
     * @return mixed
     */
    public function getGroupInfo($appid, $group_id)
    {
        return $this->db->get('mcc_filter_group', '*', [
            'AND' => [
                'filter_group_id'   => (int)$group_id,
                'appid'             => (int)$appid
            ]
        ]);
    }

    /**
     * 根据group_id获得筛选列表
     * @param $appid
     * @param $group_id
     * @return mixed
     */
    public function getFilterListByGroupId($appid, $group_id)
    {
        return $this->db->select('mcc_filter', '*', [
            'AND' => [
                'filter_group_id'   => (int)$group_id,
                'appid'             => (int)$appid
            ]
        ]);
    }

    /**
     * 获得分组列表
     * @param array $data
     * @return mixed
     */
    public function getFilterGroups($appid, $sort, $order, $start, $limit)
    {
        $count = $this->db->query("SELECT count(1) AS count FROM `mcc_filter_group` WHERE appid = " . $appid)
            ->fetchColumn ();
        if (0 == $count){
            return ["count" => 0, "list" => []];
        }
        $sql = "SELECT * FROM `mcc_filter_group` WHERE appid = " . $appid;
        $sort_data = [
            'name',
            'sort_order'
        ];
        if (!empty($sort) && in_array($sort, $sort_data)) {
            $sql .= " ORDER BY " . $sort;
        } else {
            $sql .= " ORDER BY name";
        }
        if (!empty($order) && ($order == 'DESC')) {
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
        $list = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return ["count" => $count, "list" => $list];
    }

    /**
     * 根据filter_id获得筛选信息
     * @param $appid
     * @param $filter_id
     * @return mixed
     */
    public function getFilterInfo($appid, $filter_id)
    {
        if (is_array($filter_id)) {
            return $this->db->select('mcc_filter', '*', [
                'AND' => [
                    'filter_id'   => $filter_id,
                    'appid'       => (int)$appid
                ]
            ]);
        } else {
            return $this->db->get('mcc_filter', '*', [
                'AND' => [
                    'filter_id'   => (int)$filter_id,
                    'appid'       => (int)$appid
                ]
            ]);
        }
    }

    /**
     * 编辑分组信息
     * @param $appid
     * @param $group_id
     * @param $name
     * @param $sort_order
     * @param $filters
     * @return int
     */
    public function updateGroup($appid, $group_id, $name, $sort_order, $filters)
    {
        $db = $this->db;
        $status = 0;
        $db->action(function($db) use ($appid, $group_id, $name, $sort_order, $filters, &$status) {
            $db->update("mcc_filter_group", [
                'name'              => $name,
                'sort_order'        => (int)$sort_order
            ], [
                "AND"   => [
                    'appid'             => (int)$appid,
                    'filter_group_id'   => (int)$group_id
                ]
            ]);
            $db->delete('mcc_filter', [
                'AND'   => [
                    'appid'             => (int)$appid,
                    'filter_group_id'   => (int)$group_id
                ]
            ]);
            foreach ($filters as $filter) {
                $last_id = $db->insert("mcc_filter", [
                    'appid'             => (int)$appid,
                    'name'              => $filter['filter_name'],
                    'filter_group_id'   => (int)$group_id,
                    'sort_order'        => (int)$filter['sort_order']
                ]);
                if(!$last_id){
                    return false;
                }
            }
            $status = $last_id;
            return true;
        });
        return $status;
    }

    /**
     * 删除分组数据
     * @param $appid
     * @param $group_id
     */
    public function deleteGroup($appid, $group_id)
    {
        if (!is_array($group_id)) {
            $group_id = (int)$group_id;
        }
        $db = $this->db;
        $db->action(function($db) use ($appid, $group_id) {
            $db->delete('mcc_filter_group', [
                'AND'   => [
                    'appid'             => (int)$appid,
                    'filter_group_id'   => $group_id
                ]
            ]);
            $db->delete('mcc_filter', [
                'AND'   => [
                    'appid'             => (int)$appid,
                    'filter_group_id'   => $group_id
                ]
            ]);
            return true;
        });
        return true;
    }

    /**
     * 获得列表
     * @param array $data
     */
    public function getFilterList($appid, $filter_data = [])
    {
        $sql = "SELECT *, (SELECT name FROM mcc_filter_group fg
            WHERE f.filter_group_id = fg.filter_group_id AND fg.appid = " . $appid
            . ") AS `group` FROM mcc_filter f WHERE f.appid = " . $appid;
        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND f.name LIKE '" . $filter_data['filter_name'] . "%'";
        }
        if (!empty($filter_data['filter_ids'])) {
            $sql .= " AND f.filter_id in (" . implode(', ', $filter_data['filter_ids']) . ")";
        }
        $sql .= " ORDER BY f.sort_order ASC";
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
} 