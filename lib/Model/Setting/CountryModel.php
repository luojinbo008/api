<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/9/19
 * Time: 14:00
 */

namespace Lib\Model\Setting;

use Lib\Model\BaseModel;

class CountryModel extends BaseModel
{
    /**
     * 获得国家信息
     */
    public function getCountryInfo($country_id)
    {
        return $this->db->get('mcc_country', '*', [
            'AND'   => [
                'country_id'    => (int)$country_id
            ]
        ]);
    }
}