<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/9/20
 * Time: 17:30
 */

namespace Lib\Payment\AliPayAPI;
use \Lib\Payment\AliPayAPI\Lib\AliPaySubmit,
    \Lib\Payment\AliPayAPI\Lib\AliPayNotify,
    \Lib\Payment\OrderCheck;

class Payment
{
    public $config = null;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * wap下单
     * @param $out_trade_no
     * @param $subject
     * @param $total
     * @return Lib\提交表单HTML文本
     */
    public function payOrderByWap($out_trade_no, $subject, $total)
    {
        $parameter = [
            "service"           => 'alipay.wap.create.direct.pay.by.user',
            "partner"           => $this->config['partner'],
            "seller_id"         => $this->config['seller_id'],
            "payment_type"	    => $this->config['payment_type'],
            "notify_url"	    => $this->config['notify_url'],
            "return_url"	    => $this->config['return_url'],
            "_input_charset"	=> trim(strtolower($this->config['input_charset'])),
            "out_trade_no"	    => $out_trade_no,
            "subject"	        => $subject,
            "total_fee"	        => $total,
        ];
        $submit = new AliPaySubmit($this->config);
        $html = $submit->buildRequestForm($parameter, "get", "确认");
        return $html;
    }

    /**
     * 通知单
     * @param $data
     * @return bool
     */
    public function notify($data)
    {
        $notify = new AlipayNotify($this->alipay_config);
        $verify_result = $notify->verifyNotify($data);
        if ($verify_result) {
            $out_trade_no = $data['out_trade_no'];
            $trade_no = $data['trade_no'];
            $trade_status = $data['trade_status'];
            if ($trade_status == 'TRADE_FINISHED') {
                // 普通即时到账的交易成功状态
                $orderCheck = new OrderCheck();
                $checkStatus = $orderCheck->checkOrder($this->appid, $out_trade_no, $trade_no, 'alipay');
                if ($checkStatus) {
                    return true;
                }
                return false;
            } else if ($trade_status == 'TRADE_SUCCESS') {
                //开通了高级即时到账或机票分销产品后的交易成功状态
                $orderCheck = new OrderCheck();
                $checkStatus = $orderCheck->checkOrder($this->appid, $out_trade_no, $trade_no, 'alipay');
                if ($checkStatus) {
                    return true;
                }
                return false;
            }
            return true;
        }
        else {
            return false;
        }
    }
}