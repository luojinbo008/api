<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/9/12
 * Time: 10:27
 */

namespace Lib\Model\Customer;

use Lib\Model\BaseModel;

class AddressModel extends BaseModel
{
    /**
     * 新增收货地址
     * @param $appid
     * @param $customer_id
     * @param $fullname
     * @param $company
     * @param $address
     * @param $postcode
     * @param $city
     * @param $zone_id
     * @param $country_id
     * @param $shipping_telephone
     * @param $custom_field
     * @return mixed
     */
    public function addAddress($appid, $customer_id, $fullname, $company, $address, $postcode,
                               $city, $zone_id, $country_id, $shipping_telephone, $custom_field, $default = false)
    {
        $data = [
            'appid'                 => (int)$appid,
            'customer_id'           => (int)$customer_id,
            'fullname'              => $this->escape($fullname),
            'company'               => $this->escape($company),
            'address'               => $this->escape($address),
            'postcode'              => $this->escape($postcode),
            'city'                  => $this->escape($city),
            'zone_id'               => (int)$zone_id,
            'country_id'            => $country_id,
            'shipping_telephone'    => $this->escape($shipping_telephone),
        ];
        if (is_array($custom_field) || !empty($custom_field)) {
            $data["custom_field"] = json_encode($custom_field, true);
        } else {
            $data["custom_field"] = '';
        }
        $address_id = $this->db->insert('mcc_address', $data);
        $total_address = $this->getTotalAddresses($appid, $customer_id);
        if ($total_address == 0 || $default) {
            $this->db->update('mcc_customer', [
                'address_id'    => (int)$address_id,
            ], [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'customer_id'   => (int)$customer_id
                ]
            ]);
        }
        return $address_id;
    }

    /**
     * 编辑用户地址信息
     * @param $appid
     * @param $address_id
     * @param $customer_id
     * @param $fullname
     * @param $company
     * @param $address
     * @param $postcode
     * @param $city
     * @param $zone_id
     * @param $country_id
     * @param $shipping_telephone
     * @param $custom_field
     * @param bool $default
     * @return mixed
     */
    public function updateAddress($appid, $address_id, $customer_id, $fullname, $company, $address, $postcode,
                               $city, $zone_id, $country_id, $shipping_telephone, $custom_field, $default = false)
    {
        $data = [
            'fullname'              => $this->escape($fullname),
            'company'               => $this->escape($company),
            'address'               => $this->escape($address),
            'postcode'              => $this->escape($postcode),
            'city'                  => $this->escape($city),
            'zone_id'               => (int)$zone_id,
            'country_id'            => $country_id,
            'shipping_telephone'    => $this->escape($shipping_telephone),
        ];
        if (is_array($custom_field) || !empty($custom_field)) {
            $data["custom_field"] = json_encode($custom_field, true);
        } else {
            $data["custom_field"] = '';
        }
        $this->db->update('mcc_address', $data, [
            'AND' => [
                'appid'         => (int)$appid,
                'customer_id'   => (int)$customer_id,
                'address_id'    => (int)$address_id,
            ]
        ]);
        if ($default) {
            $this->db->update('mcc_customer', [
                'address_id'    => (int)$address_id,
            ], [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'customer_id'   => (int)$customer_id
                ]
            ]);
        }
    }

    /**
     * 获得用户地址个数
     * @param $appid
     * @param $customer_id
     * @return mixed
     */
    public function getTotalAddresses($appid, $customer_id)
    {
        return $this->db->count('mcc_address', [
            'AND'   => [
                'appid'                 => (int)$appid,
                'customer_id'           => (int)$customer_id,
            ]
        ]);
    }

    /**
     * 获得用户地址列表
     * @param $appid
     * @param $customer_id
     * @return mixed
     */
    public function getAddressList($appid, $customer_id)
    {
        $count = $this->getTotalAddresses($appid, $customer_id);
        if ($count <= 0) {
            return ['count' => 0, 'list' => []];
        }
        $list = $this->db->select('mcc_address', '*', [
            'AND'   => [
                'appid'                 => (int)$appid,
                'customer_id'           => (int)$customer_id,
            ]
        ]);
        return ['count' => $count, 'list' => $list];
    }

    /**
     * 获得地址信息
     * @param $appid
     * @param $customer_id
     * @param $address_id
     * @return mixed
     */
    public function getAddressInfo($appid, $customer_id, $address_id)
    {
        return $this->db->get('mcc_address', '*', [
            'AND'   => [
                'appid'         => $appid,
                'customer_id'   => $customer_id,
                'address_id'    => $address_id,
            ]
        ]);
    }

    /**
     * 删除用户信息
     * @param $appid
     * @param $customer_id
     * @param $address_id
     */
    public function deleteAddress($appid, $customer_id, $address_id, $default_address_id)
    {
        $db = $this->db;
        $status = false;
        $db->action(function($db) use ($appid, $customer_id, $address_id, $default_address_id, &$status) {
            $statusDel = $db->delete('mcc_address', [
                'AND' => [
                    'appid' => $appid,
                    'customer_id' => $customer_id,
                    'address_id' => $address_id,
                ]
            ]);
            if (!$statusDel) {
                return false;
            }
            if ($statusDel && $default_address_id == $address_id) {
                $info = $db->get('mcc_address', '*', [
                    'AND'   => [
                        'appid'                 => (int)$appid,
                        'customer_id'           => (int)$customer_id,
                    ]
                ]);
                $default_address_id = $info ? $info['address_id'] : 0;
                $statusDefault = $this->db->update('mcc_customer', [
                    'address_id'    => (int)$default_address_id,
                ], [
                    'AND'   => [
                        'appid'         => (int)$appid,
                        'customer_id'   => (int)$customer_id
                    ]
                ]);
                if (!$statusDefault) {
                    return false;
                }
            }
            $status = true;
            return true;
        });
        return $status;
    }
}