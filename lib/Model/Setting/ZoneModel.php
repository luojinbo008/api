<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/9/12
 * Time: 9:25
 */

namespace Lib\Model\Setting;
use Lib\Model\BaseModel;

class ZoneModel extends BaseModel
{
    /**
     * 获得区域列表
     * @param $country_id
     * @return mixed
     */
    public function getZonesByCountryId($country_id)
    {
        $where = [
            'AND'   => [
                'status'    => 1,
            ],
            'ORDER' => [
                'code'  => 'ASC'
            ]
        ];
        if ($country_id) {
            $where['AND']['country_id'] = (int)$country_id;
        }
        return $this->db->select('mcc_zone', '*', $where);
    }

    /**
     * 获得区域信息
     * @param $zone_id
     * @return mixed
     */
    public function getZoneInfo($zone_id)
    {
        return $this->db->get('mcc_zone', '*', [
            'AND'   => [
                'zone_id' => (int)$zone_id
            ]
        ]);
    }
}