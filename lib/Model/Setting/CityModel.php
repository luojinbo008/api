<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/9/11
 * Time: 17:31
 */

namespace Lib\Model\Setting;

use Lib\Model\BaseModel;

class CityModel extends BaseModel
{

    /**
     * 获得城市列表
     * @return mixed
     */
    public function getCities($zone_id)
    {
        $where = [
            'AND'   => [
                'status'    => 1,
            ],
            'ORDER' => [
                'name'  => 'ASC'
            ]
        ];
        if ($zone_id) {
            $where['AND']['zone_id'] = (int)$zone_id;
        }
        return $this->db->select('mcc_city', '*', $where);
    }
}