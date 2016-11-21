<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/8/25
 * Time: 14:46
 */

namespace App\Controllers;
use Lib\Model\Customer\AddressModel;
use \Psr\Http\Message\ServerRequestInterface as Request,
    \Psr\Http\Message\ResponseInterface as Response,
    \Lib\Model\Order\OrderModel,
    \Lib\Model\Customer\CustomerModel,
    \Lib\Model\Customer\CustomerGroupModel,
    \Lib\Model\Product\ProductModel,
    \Lib\Model\Setting\StoreModel,
    \Lib\Model\Setting\CountryModel,
    \Lib\Model\Setting\ZoneModel,
    \Lib\Model\Setting\PaymentConfigModel,
    \Lib\Payment\WxPayAPI\Payment as WXPayment,
    \Lib\Payment\WxPayAPI\PayNotifyCallBack as WXPayNotifyCallBack,
    \Lib\Payment\AliPayAPI\Payment as AlipayPayment,
    \Lib\Model\Setting\WechatAuthorizationModel;

class OrderController extends BaseController
{
    /**
     * 支付订单
     * @param Request $request
     * @param Response $response
     */
    public function payOrder(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $order_id = $data['order_id'];
        $payment = $data['payment'];
        $customer_id = (int)$data['customer_id'];
        $name = $data['name'];

        // 验证订单是否已生成
        $orderModel = new OrderModel();
        $orderInfo = $orderModel->getOrderInfo($this->appid, $order_id);
        if(!$orderInfo || $orderInfo['customer_id'] != $customer_id) {
            return $response->withJson(["code" => 1, "message"=>"订单不存在！", 'data'=>[]]);
        }
        if (!in_array($orderInfo['order_status_id'], [ORDER_STATUS_START, ORDER_STATUS_PAY])) {
            return $response->withJson(["code" => 1, "message" => "订单状态为不可支付！", "data" => []]);
        }

        $customerModel = new CustomerModel();
        $customerInfo = $customerModel->getCustomerInfo($this->appid, $customer_id);
        if (!$customerInfo) {
            return $response->withJson(["code" => 1, "message" => "用户不存在！", "data" => []]);
        }
        // 插入日志
        $orderModel->addOrderHistory($this->appid, $order_id, ORDER_STATUS_PAY);
        $return = [];
        $paymentConfigModel = new PaymentConfigModel();
        switch ($payment) {
            case 'jsWechat' :
                if (empty($customerInfo['weixin_openid'])) {
                    return $response->withJson(["code" => 1, "message" => "账号没有对于微信账号！", "data" => []]);
                }
                $row = $paymentConfigModel->getConfigByType($this->appid, 'wechat');
                $config = array_column($row, 'value', 'key');
                $keys = array_column($row, 'key');
                if (!empty(array_diff_key([
                    'mchid', 'key', 'status', 'authorization', 'is_https'
                ],$keys))) {
                    throw new \Exception("配置参数错误！");
                }
                $wechatAuthorizationModel =  new WechatAuthorizationModel();
                $wechatInfo = $wechatAuthorizationModel->getInfo($this->appid);
                if (!$wechatInfo) {
                    throw new \Exception("请先授权收款帐号对应微信公众号！");
                }
                $config['appid'] = $wechatInfo['authorizer_appid'];
                $config = array_merge($config, \Lib\Environment\Config::getConfig('payment', 'wechat'));
                $config['notify_url'] .= "/" . $this->appid;
                $wxPayment = WXPayment::init($config);
                $return = $wxPayment->payOrderByJs($customerInfo['weixin_openid'], $order_id, $name, $orderInfo['total']);
                break;
            case 'alipayByWechat' :
                $row = $paymentConfigModel->getConfigByType($this->appid, 'alipay');
                $config = array_column($row, 'value', 'key');
                $keys = array_column($row, 'key');
                if (!empty(array_diff_key([
                    'partner', 'seller_id', 'private_key_path', 'ali_public_key_path', 'sign_type'
                ],$keys))) {
                    throw new \Exception("配置参数错误！");
                }
                $config = array_merge($config, \Lib\Environment\Config::getConfig('payment', 'alipay'));
                $config['notify_url'] .= "/" . $this->appid;
                $alipayPayment = new AlipayPayment($config);
                $return = $alipayPayment->payOrderByWap($order_id, $name, $orderInfo['total']);
                break;
        }
        return $response->withJson(["code" => 0, "message" => "支付下单成功！", "data" => ['info' => $return]]);
    }

    /**
     * 支付后回调
     * @param Request $request
     * @param Response $response
     */
    public function submitOrder(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $payment = $query['payment'];
        $data = $query['data'];
        $store_id = $query['store_id'];
        $return = '';
        switch ($payment) {
            case 'wechat':
                $notify = new WXPayNotifyCallBack();
                $return = $notify->Handle($this->appid, $store_id, false, $data);
                break;
            case 'alipay':
                $alipayPayment = new AlipayPayment($this->appid, $store_id);
                $status = $alipayPayment->notify($data);
                $return = 'fail';
                if ($status) {
                    $return = 'success';
                }
            default :
        }
        if (empty($return)) {
            return $response->withJson(["code" => 1, "message" => "回调方式不正确，联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "success", "data" => [
            'return' => $return
        ]]);
    }

    /**
     * 订单联系地址
     * @param Request $request
     * @param Response $response
     */
    public function checkShippingAddress(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $customer_id = (int)$data['customer_id'];
        $info['order_id'] = (int)$data['order_id'];

        $info['shipping_address_format'] = isset($data['shipping_address_format']) ? $data['shipping_address_format'] : '';
        $info['shipping_method'] = isset($data['shipping_method']) ? $data['shipping_method'] : '';
        $info['shipping_code'] = isset($data['shipping_code']) ? $data['shipping_code'] : '';

        $addressModel = new AddressModel();
        $addressInfo = $addressModel->getAddressInfo($this->appid, $customer_id, $data['address_id']);
        if (!$addressInfo) {
            return $response->withJson(["code" => 1, "message" => "没有该联系地址！", "data" => []]);
        }
        $orderModel = new OrderModel();
        $orderInfo = $orderModel->getOrderInfo($this->appid, $info['order_id']);
        if (!$orderInfo || $orderInfo['customer'] || !in_array($orderInfo['order_status_id'], [ORDER_STATUS_START,
                ORDER_STATUS_PAY])) {
            return $response->withJson(["code" => 1, "message" => "没有该订单， 或订单已经结束！", "data" => []]);
        }
        $info['shipping_country_id'] = $addressInfo['country_id'];
        $info['shipping_country'] = '';
        if (!empty($addressInfo['country_id'])) {
            $countryModel = new CountryModel();
            $countryInfo = $countryModel->getCountryInfo($addressInfo['country_id']);
            $info['shipping_country'] = $countryInfo ? $countryInfo['name'] : '未知';
        }

        $info['shipping_zone_id'] = $addressInfo['zone_id'];
        $info['shipping_zone'] = '';
        if (!empty($addressInfo['shipping_zone_id'])) {
            $zoneModel = new ZoneModel();
            $zoneInfo = $zoneModel->getZoneInfo($addressInfo['zone_id']);
            $info['shipping_zone'] = $zoneInfo ? $zoneInfo['name'] : '未知';
        }
        $info['shipping_fullname'] = $addressInfo['fullname'];
        $info['shipping_telephone'] = $addressInfo['shipping_telephone'];
        $info['shipping_company'] = $addressInfo['company'];
        $info['shipping_address'] = $addressInfo['address'];
        $info['shipping_city'] = $addressInfo['city'];
        $info['shipping_postcode'] = $addressInfo['postcode'];
        $info['shipping_custom_field'] = $addressInfo['custom_field'];
        $orderModel->deleteOrderAddress($this->appid, $info['order_id']);
        $status = $orderModel->addOrderAddress($this->appid, $info);
        if (!$status) {
            return $response->withJson(["code" => 1, "message" => "编辑联系方式失败，联系管理员！", "data" => []]);
        } else {
            return $response->withJson(["code" => 0, "message" => "编辑联系方式成功！", "data" => []]);
        }
    }

    /**
     * 根据商品直接生成订单
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function addOrderByProduct(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $store_id = (int) $data['store_id'];
        $product_id = (int) $data['product_id'];
        $customer_id = (int) $data['customer_id'];
        $option = $data['option'];
        $quantity = (int)$data['quantity'];
        $ip = $data['ip'];
        if (!$quantity) {
           $quantity = 1;
        }
        $customerModel = new CustomerModel();
        $customerInfo = $customerModel->getStoreCustomerInfo($this->appid, $store_id, 'customer_id', $customer_id);
        if (!$customerInfo) {
           return $response->withJson(["code" => 1, "message" => "没有该用户！", "data" => []]);
        }
        $productModel = new ProductModel();
        $product_info = $productModel->getProductDetailByCustomerGroupId($this->appid, $store_id,
           $product_id, $customerInfo['customer_group_id'], $quantity);
        if (!$product_info) {
           return $response->withJson(["code" => 1, "message" => "商品不存在！", "data" => []]);
        }
        if (empty($option)) {
           $option = [];
        }
        $product_options = $productModel->getProductOptions($this->appid, $product_id);
        foreach ($product_options as $product_option) {
           if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
               return $response->withJson(["code" => 1, "message" => sprintf("%s 必须！", $product_option['name']), "data" => []]);
           }
        }
        $product = $productModel->getStoreProductInfoByOption($this->appid, $product_id, $option, $customerInfo['customer_group_id'], $quantity);
        if (!$product['stock']) {
            return $response->withJson(["code" => 1, "message" => "【" . $product['name'] . "】库存不足，请联系管理员！", "data" => ['order_id' => []]]);
        }
        $products = [$product];
        $total = $product['total'];
        $orderModel = new OrderModel();
        $comment = '';
        $order_id = $orderModel->addOrder($this->appid, $store_id, $customerInfo, $products, $total, $ip, $comment);
        if (!$order_id) {
            return $response->withJson(["code" => 1, "message" => "订单生成失败！", "data" => []]);
        }

        $orderModel->addOrderHistory($this->appid, $order_id, ORDER_STATUS_START);
        return $response->withJson(["code" => 0, "message" => "订单生成成功！", "data" => ['order_id' => $order_id]]);
    }

    /**
     * 商城用户获得我的订单列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getMyOrderList(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $start = (int)$data['start'];
        $limit = (int)$data['limit'];
        $store_id = (int)$data['store_id'];
        $customerId = (int)$data['customer_id'];
        $status = $data['status'];
        if (empty($customerId)) {
            return $response->withJson(["code" => 1, "message" => "参数错误！", "data" => []]);
        }
        $model = new OrderModel();
        $list = $model->getOrderListByCustomerId($this->appid, $store_id, $customerId, $status, $start, $limit);
        return $response->withJson(["code" => 0, "message" => "获得我的订单列表成功！", "data" => $list]);
    }

    /**
     * 商城用户获得订单详细信息
     * @param Request $request
     * @param Response $response
     */
    public function getMyOrderInfo(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $store_id = (int)$data['store_id'];
        $customer_id = (int)$data['customer_id'];
        $order_id = (int)$data['order_id'];
        $orderModel = new OrderModel();
        $order_info = $orderModel->getOrderInfo($this->appid, $order_id);
        if (!$order_info || $customer_id != $order_info['customer_id'] || $store_id != $order_info['store_id'] ) {
           return $response->withJson(["code" => 1, "message" =>  "没有订单信息！", "data" => []]);
        }
        $product_list = $orderModel->getOrderProducts($this->appid, $order_id);
        $order_info['product_list'] = $product_list;
        $shipping = 0;
        foreach ($product_list as $product) {
            if ($product['shipping']) {
                $shipping = 1;
            }
        }
        $order_info['shipping'] = $shipping;
        $customerGroupModel = new CustomerGroupModel();
        $customer_group_info = $customerGroupModel->getCustomerGroupInfo($this->appid, $order_info['customer_group_id']);
        $order_info['customer_group_info'] = $customer_group_info;
        $storeModel = new StoreModel();
        $store_info = $storeModel->getStoreInfo($this->appid, $order_info['store_id']);
        $order_info['store_info'] = $store_info;
        $address_list = $orderModel->getOrderAddressList($this->appid, $order_id);
        $order_info['address_list'] = $address_list;
        return $response->withJson(["code" => 0, "message" =>  "获得订单信息成功！", "data" => ['info' => $order_info]]);
    }
    /**
     * 获得订单列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getOrderList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $filters = [];
        if (isset($query['filter_order_status'])) {
            $filters['filter_order_status'] = $query['filter_order_status'];
        }
        if (isset($query['filter_order_id'])) {
            $filters['filter_order_id'] = $query['filter_order_id'];
        }
        if (isset($query['filter_customer'])) {
            $filters['filter_customer'] = $query['filter_customer'];
        }
        if (isset($query['filter_date_added'])) {
            $filters['filter_date_added'] = $query['filter_date_added'];
        }
        if (isset($query['filter_date_modified'])) {
            $filters['filter_date_modified'] = $query['filter_date_modified'];
        }
        if (isset($query['filter_total'])) {
            $filters['filter_total'] = $query['filter_total'];
        }
        if (isset($query['order'])) {
            $filters['order'] = $query['order'];
        }
        if (isset($query['sort'])) {
            $filters['sort'] = $query['sort'];
        }
        $filters['start'] = $filters['limit'] = 0;
        if (isset($query['start'])) {
            $filters['start'] = $query['start'];
        }
        if (isset($query['limit'])) {
            $filters['limit'] = $query['limit'];
        }
        $model = new OrderModel();
        $data = $model->getOrderList($this->appid, $filters);
        return $response->withJson(["code" => 0, "message" =>  "获得订单列表成功！", "data" => $data]);
    }

    /**
     * 获得订单信息
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getOrderInfo(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $order_id = $query['order_id'];
        $orderModel = new OrderModel();
        $order_info = $orderModel->getOrderInfo($this->appid, $order_id);
        if (!$order_info) {
            return $response->withJson(["code" => 1, "message" =>  "没有订单信息！", "data" => []]);
        }
        $address_list = $orderModel->getOrderAddressList($this->appid, $order_id);
        $order_info['address_list'] = $address_list;
        $product_list = $orderModel->getOrderProducts($this->appid, $order_id);
        $order_info['product_list'] = $product_list;
        $customerGroupModel = new CustomerGroupModel();
        $customer_group_info = $customerGroupModel->getCustomerGroupInfo($this->appid, $order_info['customer_group_id']);
        $order_info['customer_group_info'] = $customer_group_info;
        $storeModel = new StoreModel();
        $store_info = $storeModel->getStoreInfo($this->appid, $order_info['store_id']);
        $order_info['store_info'] = $store_info;
        return $response->withJson(["code" => 0, "message" =>  "获得订单列表成功！", "data" => ['info' => $order_info]]);
    }

    /**
     * 获得订单历史操作列表
     * @param Request $request
     * @param Response $response
     * @return array
     */
    public function getOrderHistories(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $order_id = $query['order_id'];
        $filter_data = [];
        if (isset($query['start'])) {
            $filter_data['start'] = $query['start'];
        }
        if (isset($query['limit'])) {
            $filter_data['limit'] = $query['limit'];
        }
        $orderModel = new OrderModel();
        $data = $orderModel->getOrderHistories($this->appid, $order_id, $filter_data);
        return $response->withJson(["code" => 0, "message" =>  "获得订单历史操作列表成功！", "data" => $data]);
    }

    /**
     * 订单统计
     * @param Request $request
     * @param Response $response
     */
    public function getOrderStatistics(Request $request, Response $response)
    {
        $orderModel = new OrderModel();
        $today_filter_data['filter_date_added'] = date('Y-m-d', CURRENT_TIME - 24 * 3600);
        $data['today_count'] = $orderModel->getTotalOrders($this->appid, $today_filter_data);
        $yesterday_filter_data['filter_date_added'] = date('Y-m-d', CURRENT_TIME - 24 * 3600);
        $data['yesterday_count'] = $orderModel->getTotalOrders($this->appid, $yesterday_filter_data);
        $data['all_total'] = $orderModel->getTotalOrders($this->appid);

        $data['today_sale'] = $orderModel->getTotalSales($this->appid, $today_filter_data);
        $data['yesterday_sale'] = $orderModel->getTotalSales($this->appid, $yesterday_filter_data);
        $data['all_sale'] = $orderModel->getTotalSales($this->appid);

        return $response->withJson(["code" => 0, "message" =>  "获得订单统计成功！", "data" => $data]);
    }

    /**
     * 统计明细
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getOrderStatisticsDetail(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $type = $query['type'];
        $orderModel = new OrderModel();
        $data = [];
        switch ($type) {
            case 'day':
                $results = $orderModel->getTotalOrdersByDay($this->appid);
                foreach ($results as $key => $value) {
                    $data[] = [$key, $value['total']];
                }
                break;
            case 'week':
                $results = $orderModel->getTotalOrdersByWeek($this->appid);
                foreach ($results as $key => $value) {
                    $data[] = [$key, $value['total']];
                }
                break;
            case 'month':
                $results = $orderModel->getTotalOrdersByMonth($this->appid);
                foreach ($results as $key => $value) {
                    $data[] = [$key, $value['total']];
                }
                break;
            case 'year':
                $results = $orderModel->getTotalOrdersByYear($this->appid);
                foreach ($results as $key => $value) {
                    $data[] = [$key, $value['total']];
                }
                break;
        }
        return $response->withJson(["code" => 0, "message" =>  "获得订单统计成功！", "data" => ['list' => $data]]);
    }

    /**
     * 用户申请退款
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function refundOrder(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $order_id = (int)$data['order_id'];
        $customer_id = (int)$data['customer_id'];
        $comment = $data['comment'];
        if (empty($comment)) {
            return $response->withJson(["code" => 1, "message" => "请输入退款说明！", "data" => []]);
        }
        $model = new OrderModel();
        $info = $model->getOrderInfo($this->appid, $order_id);
        if (!$info || !in_array($info['order_status_id'], [ORDER_STATUS_END, ORDER_STATUS_COMMENT]) || $customer_id != $info['customer_id']) {
            return $response->withJson(["code" => 1, "message" => "订单不存在，或未付款！", "data" => []]);
        }
        $model->addOrderHistory($this->appid, $order_id, ORDER_STATUS_REFUND_START, $comment);
        return $response->withJson(["code" => 0, "message" => "申请退款成功，请等待商家处理！", "data" => []]);
    }

    /**
     * 后台审核退款退款
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function refundSubmitOrder(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $order_id = (int)$data['order_id'];
        $comment = $data['comment'];
        $status = (int)$data['status'];
        if (empty($comment)) {
            return $response->withJson(["code" => 1, "message" => "请输入说明！", "data" => []]);
        }
        if (!in_array($status, [ORDER_STATUS_REFUND_FAIL, ORDER_STATUS_REFUND_END])) {
            return $response->withJson(["code" => 1, "message" => "请输入正确的退款审核状态！", "data" => []]);
        }
        $model = new OrderModel();
        $info = $model->getOrderInfo($this->appid, $order_id);
        if (!$info || !in_array($info['order_status_id'], [ORDER_STATUS_REFUND_START])) {
            return $response->withJson(["code" => 1, "message" => "订单不存在，或未发起退款！", "data" => []]);
        }
        $model->addOrderHistory($this->appid, $order_id, $status, $comment);
        return $response->withJson(["code" => 0, "message" => "退款状态变更成功，请在第三方商户及时退款处理！", "data" => []]);
    }
}
