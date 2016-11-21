<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/8/20
 * Time: 11:12
 */

namespace Lib\Model\Product;

use Lib\Model\BaseModel;

class OptionModel extends BaseModel
{
    /**
     * 新增选项
     * @param $appid
     * @param $name
     * @param $type
     * @param $sort_order
     * @param $option_values
     * @return bool
     */
    public function addOption($appid, $name, $type, $sort_order, $option_values)
    {
        $db = $this->db;
        $option_id = 0;
        $db->action(function($db) use ($appid, $name, $type, $sort_order, $option_values, &$option_id) {
            $last_option_id = $db->insert('mcc_option', [
                'type'          => $type,
                'sort_order'    => $sort_order,
                'name'          => $name,
                'appid'         => $appid
            ]);
            if (!$last_option_id) {
                return false;
            }
            if (!empty($option_values)) {
                foreach ($option_values as $option_value) {
                    $last_option_value_id = $db->insert('mcc_option_value', [
                        'appid'         => (int)$appid,
                        'option_id'     => (int)$last_option_id,
                        'image'         => html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8'),
                        'sort_order'    => (int)$option_value['sort_order'],
                        'name'          => $option_value['option_value_name'],
                    ]);
                    if (!$last_option_value_id) {
                        return false;
                    }
                }
            }
            $option_id = $last_option_id;
            return true;
        });
        if (!$option_id) {
            return false;
        }
        return true;
    }

    /**
     * 编辑选项
     * @param $appid
     * @param $option_id
     * @param $name
     * @param $type
     * @param $sort_order
     * @param $option_values
     */
    public function updateOption($appid, $option_id, $name, $type, $sort_order, $option_values)
    {
        $db = $this->db;
        $db->action(function($db) use ($appid, $option_id, $name, $type, $sort_order, $option_values) {
            $db->update('mcc_option', [
                'name'          => $name,
                'sort_order'    => $sort_order,
                'type'          => $type,
            ], [
                'AND'   => [
                    'appid'     => (int)$appid,
                    'option_id' => (int)$option_id,
                ]
            ]);
            $db->delete('mcc_option_value', [
                'AND'   => [
                    'appid'     => (int)$appid,
                    'option_id' => (int)$option_id,
                ]
            ]);
            if (!empty($option_values)) {
                foreach ($option_values as $option_value) {
                    if ($option_value['option_value_id']) {
                        $db->insert('mcc_option_value', [
                            'option_value_id'   => (int)$option_value['option_value_id'],
                            'option_id'         => $option_id,
                            'appid'             => (int)$appid,
                            'image'             => html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8'),
                            'sort_order'        => (int)$option_value['sort_order'],
                            'name'              => $option_value['option_value_name'],
                        ]);
                    } else {
                        $db->insert('mcc_option_value', [
                            'option_id'         => $option_id,
                            'appid'             => (int)$appid,
                            'image'             => html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8'),
                            'sort_order'        => (int)$option_value['sort_order'],
                            'name'              => $option_value['option_value_name'],
                        ]);
                    }
                }
            }
            return true;
        });
        return true;
    }

    /**
     * 获得选项列表
     * @param $appid
     * @param array $data
     * @return mixed
     */
    public function getOptions($appid, $data = [])
    {
        $where = [];
        $where['AND']['appid'] = $appid;
        if (isset($data['filter_name']) && !empty($data['filter_name'])) {
            $where['AND']['name[~]'] = $data['filter_name'];
        }
        if (isset($data['filter_option_ids']) && !empty($data['filter_option_ids'])) {
            $where['AND']['option_id'] = $data['filter_option_ids'];
        }
        $where['ORDER'] = ['name' => 'ASC'];
        $count = $this->db->count('mcc_option', $where);
        if (0 == $count) {
            return ['list' => [], 'count' => 0];
        }
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $where['LIMIT'] = [(int)$data['start'], (int)$data['limit']];
        }
        $results = $this->db->select('mcc_option', '*', $where);
        return ['list' => $results, 'count' => $count];
    }

    /**
     * 根据option_id获得选项值
     * @param $appid
     * @param $option_id
     * @return mixed
     */
    public function getOptionValues($appid, $option_id)
    {
        return $this->db->select('mcc_option_value', '*', [
            'AND'   => [
                'option_id' => (int)$option_id,
                'appid'     => (int)$appid,
            ],
            'ORDER' => [
                'sort_order'    => 'ASC',
                'name'          => 'ASC',
            ]
        ]);
    }

    /**
     * 获得选项信息
     * @param $appid
     * @param $option_id
     * @return bool
     */
    public function getOptionInfo($appid, $option_id)
    {
        $option_info = $this->db->get('mcc_option', '*', [
            'AND'   => [
                'appid'     => (int)$appid,
                'option_id' => (int)$option_id
            ]
        ]);
        if (!$option_info) {
            return false;
        }
        $option_values_tmp = $this->getOptionValues($appid, $option_id);
        $option_values = [];
        foreach ($option_values_tmp as $option_value) {
            if ($option_value['image']) {
                $image = $option_value['image'];
            } else {
                $image = '';
            }
            $option_values[] = [
                'option_value_id'          => $option_value['option_value_id'],
                'option_value_name'        => $option_value['name'],
                'image'                    => $image,
                'sort_order'               => $option_value['sort_order']
            ];
        }
        $option_info['option_values'] = $option_values;
        return $option_info;
    }

    /**
     * 删除选项
     * @param $appid
     * @param $option_ids
     * @return bool
     */
    public function deleteOptions($appid, $option_ids)
    {
        $db = $this->db;
        $db->action(function($db) use ($appid, $option_ids) {
            $db->delete('mcc_option', [
                'AND'   => [
                    'option_id' => $option_ids,
                    'appid'     => (int)$appid,
                ]
            ]);
            $db->delete('mcc_option_value', [
                'AND'   => [
                    'option_id' => $option_ids,
                    'appid'     => (int)$appid,
                ]
            ]);
        });
        return true;
    }

    /**
     * 获得属性基本信息
     * @param $appid
     * @param $option_value_id
     * @return mixed
     */
    public function getOptionValue($appid, $option_value_id)
    {
        return $this->db->get('mcc_option_value', '*', [
            'AND'   => [
                'option_value_id' => $option_value_id,
                'appid'           => (int)$appid,
            ]
        ]);
    }

    /**
     * 更新库存
     * @param $product_option_value_id
     * @param $num
     * @param string $ext
     * @return mixed
     */
    public function tallyQuantity($appid, $product_option_value_id, $num, $ext = "-")
    {
        return $this->db->update("mcc_product_option_value", [
          "quantity[" . $ext . "]" => (int)$num
        ], [
          "AND" => [
              'appid'                       => (int)$appid,
              "product_option_value_id"     => (int)$product_option_value_id,
              "subtract"                    => 1,
          ]
        ]);
    }
}