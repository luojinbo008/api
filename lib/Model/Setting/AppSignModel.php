<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/10/20
 * Time: 15:56
 */

namespace Lib\Model\Setting;

use Lib\Model\BaseModel;

class AppSignModel extends BaseModel
{
    public $db_name = 'appConfig';

    /**
     *
     * @param $appid
     * @return mixed
     */
    public function getAppSignInfo($appid) {
         return $this->db->get('mcc_app_sign', '*', [
            'AND'   => [
                'appid' => (int)$appid
            ]
        ]);
    }
}