<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-13
 * Time: 下午6:29
 */

namespace Lib\Model\Setting;

use Lib\Model\BaseModel;

class StoreModel extends BaseModel
{

    /**
     * 更具store_id 获得商店
     * @param $appid
     * @param $store_id
     */
    public function getStoreInfo($appid, $store_id)
    {
        if(is_array($store_id)){
            return $this->db->select('mcc_store', '*', [
                'AND'   => [
                    'store_id'  => $store_id,
                    'appid'     => (int)$appid,
                ]
            ]);
        } else {
            return $this->db->get('mcc_store', '*', [
                'AND'   => [
                    'store_id'  => (int)$store_id,
                    'appid'     => (int)$appid,
                ]
            ]);
        }
    }

    /**
     * 根据商店url 获得 商店消息
     */
    public function getStoreInfoByUrl($appid, $store_url)
    {
        return $this->db->get('mcc_store', '*', [
            'AND'   => [
               'store_url'  => $store_url,
               'appid'      => (int)$appid,
            ]
        ]);
    }
    /**
     * 获得商店对于的首页广告
     * @param $appid
     * @param $store_id
     * @return mixed
     */
    public function getAdvert($appid, $store_id)
    {
        return $this->db->select('mcc_store_advert', '*', [
            'AND'   => [
                'store_id'  => $store_id,
                'appid'     => (int)$appid,
            ]
        ]);
    }

    /**
     * 获得商店列表
     * @param $appid
     * @return mixed
     */
    public function getStores($appid, $filter_data)
    {
        $where = [
            'AND'   => [
                'appid'     => (int)$appid,
            ],
        ];
        $count = $this->db->count('mcc_store', $where);
        if (0 == $count) {
            return ['count' => 0, 'list' => []];
        }
        if (!empty($filter_data['start']) || !empty($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            $where['LIMIT'] = [$filter_data['start'], $filter_data['limit']];
        }
        $list=  $this->db->select('mcc_store', '*', $where);
        return ['count' => $count, 'list' => $list];
    }

    /**
     *
     * @param $appid
     * @param $data
     */
    public function addStore($appid, $data)
    {
        $db = $this->db;
        $store_id = 0;
        $db->action(function($db) use ($appid, $data, &$store_id) {
            $last_store_id = $this->db->insert('mcc_store', [
                'appid' => (int)$appid,
                'store_url' => $data['store_url'],
                'name' => $data['name'],
                'meta_title' => $data['meta_title'],
                'store_type' => $data['store_type'],
                'customer_group_id' => (int)$data['customer_group_id'],
                'meta_description' => $data['meta_description'],
                'meta_keyword' => $data['meta_keyword'],
                'image' => $data['image'],
                'comment' => $data['comment'],
                'stock_display' => (int)$data['stock_display'],
                'date_modified' => date('Y-m-d H:i:s', CURRENT_TIME)
            ]);
            if (!$last_store_id) {
                return false;
            }
            // 更新广告
            $db->delete('mcc_store_advert', [
                'AND' => [
                    'appid'    => (int)$appid,
                    'store_id' => (int)$last_store_id,
                ]
            ]);
            if (isset($data['advert_image']) && !empty($data['advert_image'])) {
                $data_insert = [];
                foreach ($data['advert_image'] as $advert_image) {
                    if (!isset($advert_image['image']) || !isset($advert_image['title'])
                        || !isset($advert_image['link']) || !isset($advert_image['sort_order'])) {
                        continue;
                    }
                    $data_insert_data = [
                        'store_id'      => (int)$last_store_id,
                        'appid'         => (int)$appid,
                        'image'         => $advert_image['image'],
                        'title'         => $advert_image['title'],
                        'link'          => $advert_image['link'],
                        'sort_order'    => $advert_image['sort_order'],
                        'date_modified' => date('Y-m-d H:i:s', CURRENT_TIME)
                    ];
                    if (isset($advert_image['advert_id']) && !empty($advert_image['advert_id'])) {
                        $data_insert_data['advert_id'] = $advert_image['advert_id'];
                    }
                    $data_insert[] = $data_insert_data;
                }
                if (!empty($data_insert)) {
                    $db->insert('mcc_store_advert', $data_insert);
                }
            }
            $store_id = $last_store_id;
            return true;
        });
        return $store_id;
    }

    /**
     * 更新商店信息
     * @param $appid
     * @param $store_id
     * @param $data
     * @return mixed
     */
    public function uploadStore($appid, $store_id, $data)
    {
        $db = $this->db;
        $db->action(function($db) use ($appid, $store_id, $data, &$status) {
            $db->update('mcc_store', [
                'store_url'         => $data['store_url'],
                'name'              => $data['name'],
                'meta_title'        => $data['meta_title'],
                'store_type'        => $data['store_type'],
                'customer_group_id' => (int)$data['customer_group_id'],
                'meta_description'  => $data['meta_description'],
                'meta_keyword'      => $data['meta_keyword'],
                'image'             => $data['image'],
                'comment'           => $data['comment'],
                'stock_display'     => (int)$data['stock_display'],
                'date_modified'     => date('Y-m-d H:i:s', CURRENT_TIME)
            ], [
                'AND' => [
                    'appid'     => (int)$appid,
                    'store_id'  => (int)$store_id,
                ]
            ]);

            // 更新广告
            $db->delete('mcc_store_advert', [
                'AND' => [
                    'appid'    => (int)$appid,
                    'store_id' => (int)$store_id,
                ]
            ]);
            if (isset($data['advert_image']) && !empty($data['advert_image'])) {
                $data_insert = [];
                foreach ($data['advert_image'] as $advert_image) {
                    if (!isset($advert_image['image']) || !isset($advert_image['title'])
                        || !isset($advert_image['link']) || !isset($advert_image['sort_order'])) {
                        continue;
                    }
                    $data_insert_data = [
                        'store_id'      => (int)$store_id,
                        'appid'         => (int)$appid,
                        'image'         => $advert_image['image'],
                        'title'         => $advert_image['title'],
                        'link'          => $advert_image['link'],
                        'sort_order'    => $advert_image['sort_order'],
                        'date_modified' => date('Y-m-d H:i:s', CURRENT_TIME)
                    ];
                    if (isset($advert_image['advert_id']) && !empty($advert_image['advert_id'])) {
                        $data_insert_data['advert_id'] = $advert_image['advert_id'];
                    }
                    $data_insert[] = $data_insert_data;
                }
                if (!empty($data_insert)) {
                    $db->insert('mcc_store_advert', $data_insert);
                }
            }
            return true;
        });
        return true;
    }

    /**
     * 删除商店
     * @param $appid
     * @param $store_ids
     * @return mixed
     */
    public function deleteStore($appid, $store_ids)
    {
        $db = $this->db;
        $status = false;
        $db->action(function($db) use ($appid, $store_ids, &$status) {
            $status = $db->delete('mcc_store', [
                'AND' => [
                    'appid'     => (int)$appid,
                    'store_id'  => $store_ids,
                ]
            ]);
            if (!$status) {
                return false;
            }
            $db->delete('mcc_store_advert', [
                'AND' => [
                    'appid'     => (int)$appid,
                    'store_id'  => $store_ids,
                ]
            ]);
            $db->delete('mcc_product_to_store', [
                'AND' => [
                    'appid'     => (int)$appid,
                    'store_id'  => $store_ids,
                ]
            ]);
            $db->delete('mcc_category_to_store', [
                'AND' => [
                    'appid'     => (int)$appid,
                    'store_id'  => $store_ids,
                ]
            ]);
            $status = true;
            return true;
        });
        if (!$status) {
            return false;
        }
        return true;
    }
} 