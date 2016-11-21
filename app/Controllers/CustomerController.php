<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-8-21
 * Time: 下午6:19
 */

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request,
    \Psr\Http\Message\ResponseInterface as Response,
    \Lib\Model\Customer\CustomerModel,
    \Lib\Model\Customer\CustomerGroupModel,
    \Lib\Model\Customer\CartModel,
    \Lib\Model\Product\ProductModel,
    \Lib\Model\Setting\StoreModel,
    \Lib\Model\Order\OrderModel,
    \Lib\Model\Customer\AddressModel;

class CustomerController extends BaseController
{
    /**
     * 购物车生成订单
     * @param Request $request
     * @param Response $response
     */
    public function checkoutCart(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $store_id = (int) $data['store_id'];
        $cart_id = $data['cart_id'];
        $customer_id = (int) $data['customer_id'];
        $session_id = $data['session_id'];
        $ip = $data['ip'];
        $customerModel = new CustomerModel();
        $customerInfo = $customerModel->getStoreCustomerInfo($this->appid, $store_id, 'customer_id', $customer_id);
        if (!$customerInfo) {
            return $response->withJson(["code" => 1, "message" => "没有该用户！", "data" => []]);
        }
        $cartModel = new CartModel();
        $product_list = $cartModel->getCartProductsByCustomer($this->appid, $store_id, $session_id, $customer_id,
            $customerInfo['customer_group_id'], $cart_id);
        if (empty($product_list)) {
            return $response->withJson(["code" => 1, "message" => "商品为空，无法生成订单！", "data" => []]);
        }
        $products = [];
        $total = 0;
        foreach ($product_list as $product_info) {
            if (!$product_info['stock']) {
                return $response->withJson(["code" => 1, "message" => "【" . $product_info['name'] . "】" . "库存不足，请联系管理员！", "data" => ['order_id' => []]]);
            }
            $products[] = [
                'product_id' => $product_info['product_id'],
                'model'      => $product_info['model'],
                'name'       => $product_info['name'],
                'option'     => $product_info['option'],
                'quantity'   => $product_info['quantity'],
                'subtract'   => $product_info['subtract'],
                'price'      => $product_info['price'],
                'total'      => $product_info['total'],
                'reward'     => $product_info['reward'],
                'image'      => $product_info['image'],
            ];
            $total += $product_info['total'];
        }
        $comment = '';
        $orderModel = new OrderModel();
        $order_id = $orderModel->addOrder($this->appid, $store_id, $customerInfo, $products, $total, $ip, $comment);
        if (!$order_id) {
            return $response->withJson(["code" => 1, "message" => "订单生成失败！", "data" => []]);
        }
        $orderModel->addOrderHistory($this->appid, $order_id, ORDER_STATUS_START);

        // 订单生成后，删除购物车
        $cartModel->deleteCart($this->appid, $store_id, $session_id, $customer_id, $cart_id);
        return $response->withJson(["code" => 0, "message" => "订单生成成功！", "data" => ['order_id' => $order_id]]);
    }


    /**
     * 获得购物车商品明细
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCartProducts(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $store_id = (int) $data['store_id'];
        $customer_id = (int) $data['customer_id'];
        $session_id = $data['session_id'];
        $customerModel = new CustomerModel();
        $customerInfo = $customerModel->getStoreCustomerInfo($this->appid, $store_id, 'customer_id', $customer_id);
        if (!$customerInfo) {
            return $response->withJson(["code" => 1, "message" => "没有该用户！", "data" => []]);
        }
        $cartModel = new CartModel();
        $products = $cartModel->getCartProductsByCustomer($this->appid, $store_id, $session_id, $customer_id, $customerInfo['customer_group_id']);
        return $response->withJson(["code" => 0, "message" => "获得购物车商品明细成功！", "data" => ["list" => $products]]);
    }

    /**
     * 更新购物车商品数量
     * @param Request $request
     * @param Response $response
     */
    public function changeCartProductQuantity(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $cart_id = (int)$data['cart_id'];
        $quantity = (int)$data['quantity'];
        $cartModel = new CartModel();
        $info = $cartModel->getCartInfoByCartId($this->appid, $cart_id);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" => "参数错误！", "data" => []]);
        }
        $customerModel = new CustomerModel();
        $customerInfo = $customerModel->getStoreCustomerInfo($this->appid, $info['store_id'], 'customer_id', $info['customer_id']);
        if (!$customerInfo) {
            return $response->withJson(["code" => 1, "message" => "没有该用户！", "data" => []]);
        }
        $productModel = new ProductModel();
        $product = $productModel->getStoreProductInfoByOption($this->appid, $info['product_id'], json_decode($info['option'], true), $customerInfo['customer_group_id'], $quantity);
        if (!$product) {
            return $response->withJson(["code" => 1, "message" => "不存在商品或商品已下架！", "data" => []]);
        }
        if (!$cartModel->changeCartProductQuantity($this->appid, $cart_id, $quantity)) {
            return $response->withJson(["code" => 1, "message" => "更新商品数量失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "更新商品数量成功！", "data" => ['price' => $product['price']]]);
    }

    /**
     * 添加商品倒购物车
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function addProductToCart(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $store_id= (int) $data['store_id'];
        $product_id = (int) $data['product_id'];
        $customer_id = (int) $data['customer_id'];
        $option = $data['option'];
        $session_id = $data['session_id'];
        $quantity = (int)$data['quantity'];
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
        $cartModel = new CartModel();
        $cartModel->addCartProduct($this->appid, $store_id, $session_id, $customer_id, $product_id, $option, $quantity);
        $cartCount = $cartModel->getCartProductCountByCustomer($this->appid, $store_id, $session_id, $customer_id);
        return $response->withJson(["code" => 0, "message" =>  "新增商品到购物车成功！",
            "data" => ["count" => $cartCount]]);

    }

    /**
     * 删除购物车成功
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function deleteCart(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $store_id = (int) $data['store_id'];
        $customer_id = (int) $data['customer_id'];
        $session_id = $data['session_id'];
        $cart_id = $data['cart_id'];
        $cartModel = new CartModel();
        $cartModel->deleteCart($this->appid, $store_id, $session_id, $customer_id, $cart_id);
        return $response->withJson(["code" => 0, "message" =>  "删除购物车商品成功！",
            "data" => []]);
    }

        /**
     * 获得用户购物车商品数量
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCartProductCountByCustomer(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $model = new CartModel();
        $count = $model->getCartProductCountByCustomer($this->appid, $query['store_id'], $query['session_id'],
            $query['customer_id']);
        return $response->withJson(["code" => 0, "message" =>  "获得用户购物车商品数量成功！",
            "data" => ["count" => $count]]);
    }

    /**
     * 获得商城用户基本信息
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getStoreCustomerInfo(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $store_id = $query['store_id'];
        $value = $query['value'];
        $type = $query['type'];
        $model = new CustomerModel();
        $info = $model->getStoreCustomerInfo($this->appid, $store_id, $type, $value);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" =>  "获得客户信息失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "获得客户信息成功！", "data" => ["info" => $info]]);
    }

    /**
     * 获得用户分组列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCustomerGroupList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $filters = [];
        if (isset($query['order'])) {
           $filters['order'] = $query['order'];
        }
        if (isset($query['sort'])) {
           $filters['sort'] = $query['sort'];
        }
        $start = $limit = 0;
        if (isset($query['start'])) {
           $start = $query['start'];
        }
        if (isset($query['limit'])) {
           $limit = $query['limit'];
        }
        $model = new CustomerGroupModel();
        $info = $model->getCustomerGroups($this->appid, $filters, $start, $limit);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" =>  "获得客户信息失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "获得客户信息成功！", "data" => $info]);
    }

    /**
     * 获得用户基本信息
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCustomerInfo(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $customer_id = (int)$query['customer_id'];
        $model = new CustomerModel();
        $info = $model->getCustomerInfo($this->appid, $customer_id);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" =>  "获得客户信息失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "获得客户信息成功！", "data" => ["info" => $info]]);
    }

    /**
     * 更新用户 用户分组
     * @param Request $request
     * @param Response $response
     */
    public function updateCustomerInfo(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $customer_id = (int)$data['customer_id'];
        $customer_group_id = isset($data['customer_group_id']) ? (int)$data['customer_group_id'] : null;
        $status = isset($data['status']) ? (int)$data['status'] : null;
        $fullname = isset($data['fullname']) ? $data['fullname'] : null;
        $nickname = isset($data['nickname']) ? $data['nickname'] : null;
        $telephone = isset($data['telephone']) ? $data['telephone'] : null;
        $idcard = isset($data['idcard']) ? $data['idcard'] : null;

        if ($customer_group_id != null) {
            $groupModel = new CustomerGroupModel();
            $customer_group_info = $groupModel->getCustomerGroupInfo($this->appid, $customer_group_id);
            if (!$customer_group_info) {
                return $response->withJson(["code" => 1, "message" =>  "参数错误！", "data" => []]);
            }
        }
        $customerModel = new CustomerModel();
        $result = $customerModel->updateCustomerInfo($this->appid, $customer_id, $customer_group_id, $status,
            $fullname, $nickname, $telephone, $idcard);
        if (!$result) {
            return $response->withJson(["code" => 1, "message" =>  "更新失败，联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "更新成功！", "data" => []]);
    }

    /**
     * 获得用户列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCustomerList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $filters = [];
        if (isset($query['filter_name'])) {
            $filters['filter_name'] = $query['filter_name'];
        }
        if (isset($query['filter_customer_group_id'])) {
            $filters['filter_customer_group_id'] = $query['filter_customer_group_id'];
        }
        if (isset($query['filter_telephone'])) {
            $filters['filter_telephone'] = $query['filter_telephone'];
        }
        if (isset($query['filter_status']) && '' !== $query['filter_status']) {
            $filters['filter_status'] = (int)$query['filter_status'];
        } else {
            $filters['filter_status'] = 1;
        }
        if (isset($query['filter_date_added'])) {
            $filters['filter_date_added'] = $query['filter_date_added'];
        }
        if (isset($query['filter_ip'])) {
            $filters['filter_ip'] = $query['filter_ip'];
        }
        $start = $limit = 0;
        if (isset($query['start'])) {
            $start = $query['start'];
        }
        if (isset($query['limit'])) {
            $limit = $query['limit'];
        }
        $model = new CustomerModel();
        $data = $model->getCustomerList($this->appid, $filters, $start, $limit);
        return $response->withJson(["code" => 0, "message" =>  "获得客户列表成功！", "data" => $data]);
    }

    /**
     * 获得用户统计
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCustomerStatistics(Request $request, Response $response)
    {
        $customerModel = new CustomerModel();
        $today_filter_data['filter_date_added'] = date('Y-m-d', CURRENT_TIME - 24 * 3600);
        $data['today_count'] = $customerModel->getTotalCustomers($this->appid, $today_filter_data);
        $yesterday_filter_data['filter_date_added'] = date('Y-m-d', CURRENT_TIME - 24 * 3600);
        $data['yesterday_count'] = $customerModel->getTotalCustomers($this->appid, $yesterday_filter_data);
        $data['all_total'] = $customerModel->getTotalCustomers($this->appid);
        $data['online_total'] = $customerModel->getTotalCustomersOnline();
        return $response->withJson(["code" => 0, "message" =>  "获得客户统计信息成功！", "data" => $data]);
    }

    /**
     * 统计明细
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCustomerStatisticsDetail(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $type = $query['type'];
        $customerModel = new CustomerModel();
        $data = [];
        switch ($type) {
            default:
            case 'day':
                $results = $customerModel->getTotalCustomersByDay($this->appid);
                foreach ($results as $key => $value) {
                    $data[] = [$key, $value['total']];
                }
                break;
            case 'week':
                $results = $customerModel->getTotalCustomersByWeek($this->appid);
                return $response->withJson(["code" => 0, "message" =>  "获得用户统计成功！", "data" => ['list' => $results]]);
                foreach ($results as $key => $value) {
                    $data[] = [$key, $value['total']];
                }
                break;
            case 'month':
                $results = $customerModel->getTotalCustomersByMonth($this->appid);
                foreach ($results as $key => $value) {
                    $data[] = [$key, $value['total']];
                }
                break;
            case 'year':
                $results = $customerModel->getTotalCustomersByYear($this->appid);
                foreach ($results as $key => $value) {
                    $data[] = [$key, $value['total']];
                }
                break;
        }
        return $response->withJson(["code" => 0, "message" =>  "获得用户统计成功！", "data" => ['list' => $data]]);
    }

    /**
     * 获得商城用户的积分
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getStoreCustomerPoints(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $customer_id = (int)$query['customer_id'];
        $customerModel = new CustomerModel();
        $points = $customerModel->getRewardTotal($this->appid, $customer_id);
        return $response->withJson(["code" => 0, "message" =>  "获得用户积分成功！", "data" => ['points' => $points]]);
    }

    /**
     * 微信端注册账号
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function registerByWechat(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $store_id = $data['store_id'];
        $fullname = $data['fullname'];
        $telephone = $data['telephone'];
        $ip = $data['ip'];
        $idcard = $data['idcard'];
        $openId = $data['open_id'];
        $nickname = $data['nickname'];

        if (!preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#'
            , $telephone)) {
            return $response->withJson(["code" => 1, "message" => "手机号码错误！", "data" => []]);
        }

        $storeModel = new StoreModel();
        $storeInfo = $storeModel->getStoreInfo($this->appid, $store_id);
        if (!$storeInfo) {
            return $response->withJson(["code" => 1, "message" => "商店不存在", "data" => []]);
        }

        // 验证账号重复信息
        $customerModel = new CustomerModel();
        $info = $customerModel->getStoreCustomerInfo($this->appid, $store_id, 'open_id', $openId);
        if ($info) {
            return $response->withJson(["code" => 1, "message" => "已生成账号", "data" => []]);
        }
        $count = $customerModel->getTotalCustomersByTelephone($this->appid, $store_id, $telephone);
        if ($count) {
            return $response->withJson(["code" => 1, "message" => "手机号码已被使用，无法重复使用！", "data" => []]);
        }

        // 获得默认的用户分组
        $customerId = $customerModel->addWechatCustomer($this->appid, $store_id, (int)$storeInfo['customer_group_id'],
            $nickname, $fullname, $telephone, $ip, $idcard, $openId);
        if (!$customerId) {
            return $response->withJson(["code" => 1, "message" => "新增用户错误，联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "新增用户成功！",
            "data" => ['customer_id' => $customerId]]);
    }

    /**
     * 客户增加收货地址
     * @param Request $request
     * @param Response $response
     */
    public function addCustomerAddress(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $store_id = $data['store_id'];
        $customer_id = (int)$data['customer_id'];
        $fullname = $data['fullname'];
        $shipping_telephone = $data['shipping_telephone'];
        $company = isset($data['company']) ? $data['company'] : "";
        $address = isset($data['address']) ? $data['address'] : "";
        $postcode = isset($data['postcode']) ? $data['postcode'] : "";
        $city = isset($data['city']) ? $data['city'] : "";
        $zone_id = isset($data['zone_id']) ? $data['zone_id'] : "";
        $country_id = isset($data['country_id']) ? $data['country_id'] : "";
        $custom_field = isset($data['custom_field']) ? $data['custom_field'] : [];
        $default = isset($data['default']) ? $data['default'] : false;

        $customerModel = new CustomerModel();
        $customerInfo = $customerModel->getStoreCustomerInfo($this->appid, $store_id, 'customer_id', $customer_id);
        if (!$customerInfo) {
            return $response->withJson(["code" => 1, "message" => "没有该用户！", "data" => []]);
        }

        $addressModel = new AddressModel();
        $status = $addressModel->addAddress($this->appid, $customer_id, $fullname, $company, $address, $postcode, $city,
            $zone_id, $country_id, $shipping_telephone, $custom_field, $default);
        if (!$status) {
            return $response->withJson(["code" => 1, "message" => "新增地址错误，联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "新增地址成功！", "data" => []]);
    }

    /**
     * 获得地址列表
     * @param Request $request
     * @param Response $response
     */
    public function getCustomerAddressList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $customer_id = (int)$query['customer_id'];
        $addressModel = new AddressModel();
        $data = $addressModel->getAddressList($this->appid, $customer_id);

        // 获得用户默认地址
        $customerModel = new CustomerModel();
        $info = $customerModel->getCustomerInfo($this->appid, $customer_id);
        foreach ($data['list'] as &$address) {
            $address['default'] = 0;
            if ($info['address_id'] == $address['address_id']) {
                $address['default'] = 1;
            }
        }
        unset($address);
        return $response->withJson(["code" => 0, "message" => "获得地址列表成功！", "data" => $data]);
    }

    /**
     * 设置默认地址
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function setAddressDefault(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $customer_id = (int)$data['customer_id'];
        $address_id = (int)$data['address_id'];
        $addressModel = new AddressModel();
        $info = $addressModel->getAddressInfo($this->appid, $customer_id, $address_id);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" => "地址失败！", "data" => []]);
        }
        $customerModel = new CustomerModel();
        $customerModel->updateCustomerDefaultAddress($this->appid, $customer_id, $address_id);
        return $response->withJson(["code" => 0, "message" => "设置默认地址成功！", "data" => $data]);
    }

    /**
     * 获得地址信息
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getAddressInfo(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $customer_id = (int)$data['customer_id'];
        $address_id = (int)$data['address_id'];
        $addressModel = new AddressModel();
        $info = $addressModel->getAddressInfo($this->appid, $customer_id, $address_id);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" => "获得地址失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "获得地址成功！", "data" => ['info' => $info]]);
    }

    /**
     * 编辑用户地址信息
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function updateCustomerAddress(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $address_id = $data['address_id'];
        $store_id = $data['store_id'];
        $customer_id = (int)$data['customer_id'];
        $fullname = $data['fullname'];
        $shipping_telephone = $data['shipping_telephone'];
        $company = isset($data['company']) ? $data['company'] : "";
        $address = isset($data['address']) ? $data['address'] : "";
        $postcode = isset($data['postcode']) ? $data['postcode'] : "";
        $city = isset($data['city']) ? $data['city'] : "";
        $zone_id = isset($data['zone_id']) ? $data['zone_id'] : "";
        $country_id = isset($data['country_id']) ? $data['country_id'] : "";
        $custom_field = isset($data['custom_field']) ? $data['custom_field'] : [];
        $default = isset($data['default']) ? $data['default'] : false;
        $customerModel = new CustomerModel();
        $customerInfo = $customerModel->getStoreCustomerInfo($this->appid, $store_id, 'customer_id', $customer_id);
        if (!$customerInfo) {
            return $response->withJson(["code" => 1, "message" => "没有该用户！", "data" => []]);
        }

        $addressModel = new AddressModel();
        $addressModel->updateAddress($this->appid, $address_id, $customer_id, $fullname, $company, $address, $postcode, $city,
            $zone_id, $country_id, $shipping_telephone, $custom_field, $default);
        return $response->withJson(["code" => 0, "message" => "编辑地址成功！", "data" => []]);
    }

    /**
     * 删除用户地址
     * @param Request $request
     * @param Response $response
     */
    public function deleteAddress(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $address_id = $data['address_id'];
        $store_id = (int)$data['store_id'];
        $customer_id = (int)$data['customer_id'];
        $customerModel = new CustomerModel();
        $customerInfo = $customerModel->getStoreCustomerInfo($this->appid, $store_id, 'customer_id', $customer_id);
        if (!$customerInfo) {
            return $response->withJson(["code" => 1, "message" => "没有该用户！", "data" => []]);
        }
        $addressModel = new AddressModel();
        $status = $addressModel->deleteAddress($this->appid, $customer_id, $address_id, $customerInfo['address_id']);
        if (!$status) {
            return $response->withJson(["code" => 1, "message" => "删除地址失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "删除地址成功！", "data" => []]);
    }
} 