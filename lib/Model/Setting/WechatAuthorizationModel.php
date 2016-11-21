<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/10/21
 * Time: 9:55
 */

namespace Lib\Model\Setting;

use Lib\Model\BaseModel;

class WechatAuthorizationModel extends BaseModel
{
    /**
     * 获得授权信息
     * @param $appid
     * @return mixed
     */
    public function getInfo($appid)
    {
        return $this->db->get('mcc_wechat_authorization', '*', [
            'AND'   => [
                'appid' =>  (int)$appid
            ]
        ]);
    }

    /**
     * 更新数据
     * @param $appid
     * @param $authorizer_appid
     * @param $authorizer_refresh_token
     * @param $func_info
     * @return mixed
     */
    public function modify($appid, $authorizer_appid, $authorizer_refresh_token, $func_info)
    {
        if (!$this->getInfo($appid)) {
            return $this->db->insert('mcc_wechat_authorization', [
                'appid'                     => (int)$appid,
                'authorizer_appid'          => $authorizer_appid,
                'authorizer_refresh_token'  => $authorizer_refresh_token,
                'func_info'                 => json_encode($func_info, true),
                'date_added'                => date('Y-m-d H:i:s', CURRENT_TIME),
                'date_modified'             => date('Y-m-d H:i:s', CURRENT_TIME)
            ]);
        } else {
            return $this->db->update('mcc_wechat_authorization', [
                'authorizer_appid'          => $authorizer_appid,
                'authorizer_refresh_token'  => $authorizer_refresh_token,
                'func_info'                 => json_encode($func_info, true),
                'date_modified'             => date('Y-m-d H:i:s', CURRENT_TIME)
            ], [
                'AND'   => [
                    'appid' => (int)$appid
                ]
            ]);
        }
    }

    /**
     * 根据AuthorizerAppId变更授权信息
     * @param $authorizer_appid
     * @param $authorizer_refresh_token
     * @param $func_info
     * @return mixed
     */
    public function modifyByAuthorizerAppId($authorizer_appid, $authorizer_refresh_token, $func_info)
    {
        return $this->db->update('mcc_wechat_authorization', [
            'authorizer_refresh_token'  => $authorizer_refresh_token,
            'func_info'                 => json_encode($func_info, true),
            'date_modified'             => date('Y-m-d H:i:s', CURRENT_TIME)
        ], [
            'AND'   => [
                'authorizer_appid' => $authorizer_appid
            ]
        ]);
    }

    /**
     * 根据授权方appid删除授权记录
     * @param $authorizer_appid
     * @return mixed
     */
    public function deleteByAuthorizerAppid($authorizer_appid)
    {
        return $this->db->delete('mcc_wechat_authorization', [
            'AND'   => [
                'authorizer_appid'  =>  $authorizer_appid
            ]
        ]);
    }

    /**
     * 删除授权信息
     * @param $appid
     * @return mixed
     */
    public function deleteByAppId($appid)
    {
        return $this->db->delete('mcc_wechat_authorization', [
            'AND'   => [
                'appid'  =>  (int)$appid
            ]
        ]);
    }

    /**
     * 根据授权方appid删除授权信息
     * @param $authorizer_appid
     * @return mixed
     */
    public function getInfoByAuthorizerAppid($authorizer_appid)
    {
        return $this->db->get('mcc_wechat_authorization', '*', [
            'AND'   => [
                'authorizer_appid'  =>  $authorizer_appid
            ]
        ]);
    }
}