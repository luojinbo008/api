<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/9/20
 * Time: 17:30
 */
namespace Lib\Payment\WxPayAPI;
use \Lib\Payment\WxPayAPI\Lib\WxPayData\WxPayUnifiedOrder;
use \Lib\Payment\WxPayAPI\Lib\JsApiPay as WXJsApiPay;
use \Lib\Payment\WxPayAPI\Lib\WxPayException;
use \Lib\Util\Util;
use \Lib\Payment\WxPayAPI\Lib\WxPayData\WxPayResults;
use \Lib\Payment\WxPayAPI\Lib\WxPayData\WxPayReport;

class Payment
{
    public static $init = null;
    public static function init($config)
    {
        if (null == self::$init) {
            self::$init = new PaymentBase($config);
        }
        return self::$init;
    }
}

class PaymentBase
{
    public static $config = null;

    public function __construct($config)
    {
        self::$config = $config;
    }

    /**
     * 微信 公众号 js下单
     * @param $open_id
     * @param $order_id
     * @param $name
     * @param $par_total
     * @return \Lib\Payment\WxpayAPI\Lib\json数据|Lib\成功时返回
     * @throws \Exception
     */
    public function payOrderByJs($open_id, $order_id, $name, $par_total)
    {
        $order = $this->placeOrder($open_id, $order_id, $name, $par_total);
        $tools = new WXJsApiPay();
        $data = $tools->GetJsApiParameters($order, self::$config['key']);
        return $data;
    }

    /**
     * 微信统一下单
     * @param $open_id
     * @param $order_id
     * @param $name
     * @param $par_total
     * @return \Lib\Payment\WxPayAPI\Lib\成功时返回
     */
    protected function placeOrder($open_id, $order_id, $name, $par_total)
    {
        // 微信下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($name);
        $input->SetOut_trade_no($order_id);
        $input->SetTotal_fee($par_total * 100);
        $input->SetTime_start(date("YmdHis"), CURRENT_TIME);
        $input->SetTime_expire(date("YmdHis", CURRENT_TIME + 3000));
        $input->SetNotify_url(self::$config['notify_url']);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($open_id);
        $order = $this->unifiedOrder($input, 6);
        if (isset($order['err_code'])) {
            throw new WxPayException($order['err_code_des']);
        }
        return $order;
    }

    /**
     * 统一下单，WxPayUnifiedOrder中out_trade_no、body、total_fee、trade_type必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param WxPayUnifiedOrder $inputObj
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    private function unifiedOrder($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";

        // 检测必填参数
        if (!$inputObj->IsOut_trade_noSet()) {
            throw new WxPayException("缺少统一支付接口必填参数out_trade_no！");
        } else if(!$inputObj->IsBodySet()) {
            throw new WxPayException("缺少统一支付接口必填参数body！");
        } else if(!$inputObj->IsTotal_feeSet()) {
            throw new WxPayException("缺少统一支付接口必填参数total_fee！");
        } else if(!$inputObj->IsTrade_typeSet()) {
            throw new WxPayException("缺少统一支付接口必填参数trade_type！");
        }

        //关联参数
        if ($inputObj->GetTrade_type() == "JSAPI" && !$inputObj->IsOpenidSet()) {
            throw new WxPayException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
        }

        if ($inputObj->GetTrade_type() == "NATIVE" && !$inputObj->IsProduct_idSet()) {
            throw new WxPayException("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
        }

        // 异步通知url未设置
		if (!$inputObj->IsNotify_urlSet()) {
            throw new WxPayException("统一支付接口中，缺少必填异步通知url！");
		}

        $inputObj->SetAppid(self::$config['appid']);            //公众账号ID
        $inputObj->SetMch_id(self::$config['mchid']);           //商户号
        $inputObj->SetSpbill_create_ip(Util::getClientIp());     //终端ip
        $inputObj->SetNonce_str(self::getNonceStr());           //随机字符串

        // 签名
        $inputObj->SetSign(self::$config['key']);
        $xml = $inputObj->ToXml();
        $response = self::postXmlCurl($xml, $url, false, $timeOut);

        $result = WxPayResults::Init($response, self::$config['key']);
        $startTimeStamp = self::getMillisecond();//请求开始时间
        self::reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        return $result;
    }

    /**
     *
     * 查询订单，WxPayOrderQuery中out_trade_no、transaction_id至少填一个
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param WxPayOrderQuery $inputObj
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public function orderQuery($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        //检测必填参数
        if (!$inputObj->IsOut_trade_noSet() && !$inputObj->IsTransaction_idSet()) {
            throw new WxPayException("订单查询接口中，out_trade_no、transaction_id至少填一个！");
        }
        $inputObj->SetAppid(self::$config['appid']);//公众账号ID
        $inputObj->SetMch_id(self::$config['mchid']);//商户号
        $inputObj->SetNonce_str(self::getNonceStr());//随机字符串
        $inputObj->SetSign(self::$config['key']);//签名
        $xml = $inputObj->ToXml();
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response, self::$config['wechat_key']);
        $startTimeStamp = self::getMillisecond();//请求开始时间
        self::reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        return $result;
    }

    /**
     *
     * 关闭订单，WxPayCloseOrder中out_trade_no必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param WxPayCloseOrder $inputObj
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public function closeOrder($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/closeorder";

        //检测必填参数
        if (!$inputObj->IsOut_trade_noSet()) {
            throw new WxPayException("订单查询接口中，out_trade_no必填！");
        }
        $inputObj->SetAppid(WxPayConfig::APPID);//公众账号ID
        $inputObj->SetMch_id(WxPayConfig::MCHID);//商户号
        $inputObj->SetNonce_str(self::getNonceStr());//随机字符串

        $inputObj->SetSign();//签名
        $xml = $inputObj->ToXml();

        $startTimeStamp = self::getMillisecond();//请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        self::reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        return $result;
    }


    /**
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }


    /**
     * 以post方式提交xml到对应的接口url
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     */
    private static function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();

        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        // 如果有配置代理这里就设置代理
		if (self::$config['curl_proxy_host'] != "0.0.0.0"
			&& self::$config['curl_proxy_port'] != 0) {
			curl_setopt($ch,CURLOPT_PROXY, self::$config['curl_proxy_host']);
			curl_setopt($ch,CURLOPT_PROXYPORT, self::$config['curl_proxy_port']);
		}
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // 严格校验

        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if ($useCert == true) {
            // 设置证书
            // 使用证书：cert 与 key 分别属于两个.pem文件
			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLCERT, self::$config['sslcert_path'] );
			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLKEY, self::$config['sslkey_path'] );
        }

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new \Exception("curl出错，错误码:$error");
        }
    }

    /**
     * 获取毫秒级别的时间戳
     */
    private static function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode ( " ", microtime () );
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode( ".", $time );
        $time = $time2[0];
        return $time;
    }

    /**
     *
     * 上报数据， 上报的时候将屏蔽所有异常流程
     * @param string $usrl
     * @param int $startTimeStamp
     * @param array $data
     */
    private static function reportCostTime($url, $startTimeStamp, $data)
    {
        // 如果不需要上报数据
        if (self::$config['report_leave'] == 0) {
            return;
        }

        //如果仅失败上报
        if (self::$config['report_leave'] == 1 &&
            array_key_exists("return_code", $data) &&
            $data["return_code"] == "SUCCESS" &&
            array_key_exists("result_code", $data) &&
            $data["result_code"] == "SUCCESS") {
            return;
        }

        //上报逻辑
        $endTimeStamp = self::getMillisecond();
        $objInput = new WxPayReport();
        $objInput->SetInterface_url($url);
        $objInput->SetExecute_time_($endTimeStamp - $startTimeStamp);
        //返回状态码
        if (array_key_exists("return_code", $data)) {
            $objInput->SetReturn_code($data["return_code"]);
        }
        //返回信息
        if (array_key_exists("return_msg", $data)) {
            $objInput->SetReturn_msg($data["return_msg"]);
        }
        //业务结果
        if (array_key_exists("result_code", $data)) {
            $objInput->SetResult_code($data["result_code"]);
        }
        //错误代码
        if (array_key_exists("err_code", $data)) {
            $objInput->SetErr_code($data["err_code"]);
        }
        //错误代码描述
        if (array_key_exists("err_code_des", $data)) {
            $objInput->SetErr_code_des($data["err_code_des"]);
        }
        //商户订单号
        if (array_key_exists("out_trade_no", $data)) {
            $objInput->SetOut_trade_no($data["out_trade_no"]);
        }
        //设备号
        if (array_key_exists("device_info", $data)) {
            $objInput->SetDevice_info($data["device_info"]);
        }
        $objInput->SetUser_ip(Util::getClientIp());
        try {
            self::report($objInput);
        } catch (WxPayException $e) {
            //不做任何处理
        }
    }

    /**
     * 测速上报，该方法内部封装在report中，使用时请注意异常流程
     * WxPayReport中interface_url、return_code、result_code、user_ip、execute_time_必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param WxPayReport $inputObj
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function report($inputObj, $timeOut = 1)
    {
        $url = "https://api.mch.weixin.qq.com/payitil/report";
        //检测必填参数
        if (!$inputObj->IsInterface_urlSet()) {
            throw new WxPayException("接口URL，缺少必填参数interface_url！");
        } if(!$inputObj->IsReturn_codeSet()) {
            throw new WxPayException("返回状态码，缺少必填参数return_code！");
        } if(!$inputObj->IsResult_codeSet()) {
            throw new WxPayException("业务结果，缺少必填参数result_code！");
        } if(!$inputObj->IsUser_ipSet()) {
            throw new WxPayException("访问接口IP，缺少必填参数user_ip！");
        } if(!$inputObj->IsExecute_time_Set()) {
            throw new WxPayException("接口耗时，缺少必填参数execute_time_！");
        }
        $inputObj->SetAppid(self::$config['appid']);//公众账号ID
        $inputObj->SetMch_id(self::$config['mchid']);//商户号
        $inputObj->SetUser_ip(Util::getClientIp());//终端ip
        $inputObj->SetTime(date("YmdHis", CURRENT_TIME));//商户上报时间
        $inputObj->SetNonce_str(self::getNonceStr());//随机字符串
        $inputObj->SetSign();//签名
        $xml = $inputObj->ToXml();
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        return $response;
    }

}