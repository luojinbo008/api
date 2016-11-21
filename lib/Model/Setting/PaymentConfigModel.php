<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/11/11
 * Time: 16:18
 */

namespace Lib\Model\Setting;
use Lib\Model\BaseModel;

class PaymentConfigModel extends BaseModel
{
    public $types = ['wechat', 'alipay'];


    /**
     * 获得支付配置信息
     * @param $appid
     * @param $type
     * @return mixed
     */
    public function getConfigByType($appid, $type)
    {
        if (!in_array($type, $this->types)) {
            return [];
        }
        return $this->db->select('mcc_payment_config', '*', [
            'AND'   => [
                'appid' => (int)$appid,
                'code'  => $type
            ]
        ]);
    }

    /**
     * 设置属性
     * @param $appid
     * @param $data
     * @return bool|string
     */
    public function setConfig($appid, $data)
    {
        if (empty($data)) {
            return false;
        }
        $inserts = [];
        foreach ($data as $row) {
            $inserts[] = "INSERT INTO mcc_payment_config(`appid`, `code`, `key`, `value`) VALUES  ('"
                . (int)$appid . "','" . $row['code'] . "','" . $row['key'] . "','" . $row['value'] . "')"
                . " ON DUPLICATE KEY UPDATE value = '" . $row['value'] ."'";
        }
        $sql = implode(';', $inserts);
        $this->db->exec($sql);
    }
}