<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/9/22
 * Time: 19:10
 */
namespace Lib\Model\Product;
use Lib\Model\BaseModel;
class ManufacturerModel extends BaseModel
{
    /**
     * 获得品牌,商家列表
     * @param $appid
     * @param $filterData
     * @return mixed
     */
    public function getList($appid, $filterData)
    {
        $sql = "SELECT * FROM mcc_manufacturer WHERE appid = " . $appid;
        $sql_count = "SELECT COUNT(1) as count FROM mcc_manufacturer WHERE appid = " . $appid;

        if (!empty($filterData['filter_name'])) {
            $sql .= " AND name LIKE '" . $this->escape($filterData['filter_name']) . "%'";
            $sql_count .= " AND name LIKE '" . $this->escape($filterData['filter_name']) . "%'";
        }
        $count = $this->db->query($sql_count)
            ->fetchColumn();
        if ($count == 0) {
            return ["count" => 0, "list" => []];
        }
        $sort_data = [
            'name',
            'sort_order'
        ];
        if (isset($filterData['sort']) && in_array($filterData['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $filterData['sort'];
        } else {
            $sql .= " ORDER BY name";
        }
        if (isset($filterData['order']) && ($filterData['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        if (isset($filterData['start']) || isset($filterData['limit'])) {
            if ($filterData['start'] < 0) {
                $filterData['start'] = 0;
            }
            if ($filterData['limit'] < 1) {
                $filterData['limit'] = 20;
            }
            $sql .= " LIMIT " . (int)$filterData['start'] . "," . (int)$filterData['limit'];
        }
        $list = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return ["count" => $count, "list" => $list];
    }

    /**
     * 新增品牌,商家列表
     * @param $appid
     * @param $data
     * @return int
     */
    public function addManufacturer($appid, $data)
    {
        $db = $this->db;
        $manufacturer_id = 0;
        $db->action(function($db) use ($appid, $data, &$manufacturer_id) {
            $last_manufacturer_id = $db->insert('mcc_manufacturer', [
                'appid'         => (int)$appid,
                'name'          => $data['name'],
                'image'         => isset($data['image']) ? $data['image'] : '',
                'sort_order'    => (int)$data['sort_order'] ,
            ]);
            if (!$last_manufacturer_id) {
                return false;
            }
            if (isset($data['manufacturer_store'])) {
                foreach ($data['manufacturer_store'] as $store_id) {
                    $db->insert('mcc_manufacturer_to_store', [
                        'appid'             => (int)$appid,
                        'manufacturer_id'   => (int)$last_manufacturer_id,
                        'store_id'          => (int)$store_id,
                    ]);
                }
            }
            $manufacturer_id = $last_manufacturer_id;
            return true;
        });
        return $manufacturer_id;
    }

    /**
     * 更新 品牌,商家 信息
     * @param $appid
     * @param $manufacturer_id
     * @param $data
     */
    public function updateManufacturer($appid, $manufacturer_id, $data)
    {
        $db = $this->db;
        $status = false;
        $db->action(function($db) use ($appid, $manufacturer_id, $data, &$status) {
             $db->update('mcc_manufacturer', [
                'name'          => $data['name'],
                'image'         => isset($data['image']) ? $data['image'] : '',
                'sort_order'    => (int)$data['sort_order'] ,
            ], [
                'AND' => [
                    'manufacturer_id'   => (int)$manufacturer_id,
                    'appid'             => (int)$appid,
                ]
            ]);
            $db->delete('mcc_manufacturer_to_store', [
                'AND'   => [
                    'manufacturer_id'   => (int)$manufacturer_id,
                    'appid'             => (int)$appid,
                ]
            ]);
            if (isset($data['manufacturer_store'])) {
                foreach ($data['manufacturer_store'] as $store_id) {
                    $db->insert('mcc_manufacturer_to_store', [
                        'appid'             => (int)$appid,
                        'manufacturer_id'   => (int)$manufacturer_id,
                        'store_id'          => (int)$store_id,
                    ]);
                }
            }
            $status = true;
            return true;
        });
        return $status;
    }

    /**
     * 获得品牌,商家 信息
     * @param $appid
     * @param $manufacturer_id
     * @return mixed
     */
    public function getManufacturerInfo($appid, $manufacturer_id)
    {
        return $this->db->get('mcc_manufacturer', '*', [
            'AND'   => [
                'appid'             => (int)$appid,
                'manufacturer_id'   => (int)$manufacturer_id,
            ]
        ]);
    }

    /**
     * 获得品牌,商家 信息 关联商店
     * @param $appid
     * @param $manufacturer_id
     * @return array
     */
    public function getManufacturerToStore($appid, $manufacturer_id)
    {
        $stores = $this->db->select('mcc_manufacturer_to_store', '*', [
            'AND'   => [
                'manufacturer_id'       => (int)$manufacturer_id,
                'appid'                 => (int)$appid,
            ]
        ]);
        if (!$stores) {
            return [];
        }
        return array_column($stores, 'store_id');
    }

    /**
     * 删除品牌,商家 信息 关联商店
     * @param $appid
     * @param $manufacturer_id
     * @return bool
     */
    public function deleteManufacturer($appid, $manufacturer_ids)
    {
        $db = $this->db;
        $status = false;
        $db->action(function($db) use ($appid, $manufacturer_ids, &$status) {
            $db->delete('mcc_manufacturer', [
                'AND' => [
                    'manufacturer_id'   => $manufacturer_ids,
                    'appid'             => (int)$appid,
                ]
            ]);
            $db->delete('mcc_manufacturer_to_store', [
                'AND'   => [
                    'manufacturer_id'   => $manufacturer_ids,
                    'appid'             => (int)$appid,
                ]
            ]);
            $status = true;
            return true;
        });
        return $status;
    }
}