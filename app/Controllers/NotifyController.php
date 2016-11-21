<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/11/18
 * Time: 19:19
 */

namespace App\Controllers;
use \Lib\Payment\AliPayAPI\Payment as AlipayPayment;

class NotifyController extends BaseController
{
    public function alipay(Request $request, Response $response, $args)
    {
        $appid = $args['appid'];

        $data = $request->getParsedBody();

        $alipayPayment = new AlipayPayment($this->appid);

        $status = $alipayPayment->notify($data);

        $return = 'fail';
        if ($status) {
            $return = 'success';
        }


        $result = $this->httpClient->request("POST");
        if ($result && 0 == $result['code']) {
            $this->response->appendContent($result['data']['return']);
        }
    }


    public function wechat(Request $request, Response $response, $args)
    {
        $appid = $args['appid'];

        $data = $request->getParsedBody();

        $alipayPayment = new AlipayPayment($this->appid);

        $status = $alipayPayment->notify($data);

        $return = 'fail';
        if ($status) {
            $return = 'success';
        }


        $result = $this->httpClient->request("POST");
        if ($result && 0 == $result['code']) {
            $this->response->appendContent($result['data']['return']);
        }
    }
}