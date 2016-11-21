<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-17
 * Time: 下午4:54
 */

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request,
    \Psr\Http\Message\ResponseInterface as Response,
    \Lib\Model\Setting\StoreModel,
    \Lib\Model\Setting\CityModel,
    \Lib\Model\Setting\ZoneModel,
    \Lib\Model\Setting\MenuModel,
    \Lib\Model\Setting\PaymentConfigModel,
    \Lib\Model\Customer\CustomerGroupModel;

class SettingController extends BaseController
{
    public $store_types = [
        'wechat',
    ];

    /**
     * 获得商店列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getStoreList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $filter_data['start'] = $filter_data['limit'] = 0;
        if (isset($query['start'])) {
            $filter_data['start']  = $query['start'];
        }
        if (isset($query['limit'])) {
            $filter_data['limit']  = $query['limit'];
        }
        $storeModel = new StoreModel();
        $data = $storeModel->getStores($this->appid, $filter_data);
        return $response->withJson(["code" => 0, "message" =>  "获得商店列表成功！", "data" => $data]);
    }

    /**
     * 新增商店
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function addStore(Request $request, Response $response)
    {
        $query = $request->getParsedBody();

        // 验证 参数合法性
        $data = [
            'store_url'          => $query['store_url'],
            'meta_title'         => $query['meta_title'],
            'name'               => $query['name'],
            'store_type'         => $query['store_type'],
            'customer_group_id'  => (int)$query['customer_group_id'],
        ];
        if (isset($query['meta_description']) || !empty($query['meta_description'])) {
            $data['meta_description'] = $query['meta_description'];
        } else {
            $data['meta_description'] = '';
        }
        if (isset($query['meta_keyword']) || !empty($query['meta_keyword'])) {
            $data['meta_keyword'] = $query['meta_keyword'];
        } else {
            $data['meta_keyword'] = '';
        }
        if (isset($query['image']) || !empty($query['image'])) {
            $data['image'] = $query['image'];
        } else {
            $data['image'] = '';
        }
        if (isset($query['comment']) || !empty($query['comment'])) {
            $data['comment'] = $query['comment'];
        } else {
            $data['comment'] = '';
        }
        if (isset($query['stock_display'])) {
            $data['stock_display'] = (int)$query['stock_display'];
        } else {
            $data['stock_display'] = 0;
        }
        if (isset($query['advert_image'])) {
            $data['advert_image'] = (array)$query['advert_image'];
        } else {
            $data['advert_image'] = [];
        }
        $error = $this->validateStoreForm($data);
        if (!empty($error)) {
            return $response->withJson(["code" => 1, "message" =>  "参数错误！", "data" => $error]);
        }
        $storeModel = new StoreModel();
        $last_store_id = $storeModel->addStore($this->appid, $data);
        if (!$last_store_id) {
            return $response->withJson(["code" => 1, "message" =>  "新增商店失败,联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "新增商店成功！", "data" => []]);
    }

    /**
     * 新增商店
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function uploadStore(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $store_id = (int)$query['store_id'];
        // 验证 参数合法性
        $data = [
            'store_url'          => $query['store_url'],
            'meta_title'         => $query['meta_title'],
            'name'               => $query['name'],
            'store_type'         => $query['store_type'],
            'customer_group_id'  => (int)$query['customer_group_id'],
        ];
        if (isset($query['meta_description']) || !empty($query['meta_description'])) {
            $data['meta_description'] = $query['meta_description'];
        } else {
            $data['meta_description'] = '';
        }
        if (isset($query['meta_keyword']) || !empty($query['meta_keyword'])) {
            $data['meta_keyword'] = $query['meta_keyword'];
        } else {
            $data['meta_keyword'] = '';
        }
        if (isset($query['image']) || !empty($query['image'])) {
            $data['image'] = $query['image'];
        } else {
            $data['image'] = '';
        }
        if (isset($query['comment']) || !empty($query['comment'])) {
            $data['comment'] = $query['comment'];
        } else {
            $data['comment'] = '';
        }
        if (isset($query['stock_display'])) {
            $data['stock_display'] = (int)$query['stock_display'];
        } else {
            $data['stock_display'] = 0;
        }
        if (isset($query['advert_image'])) {
            $data['advert_image'] = (array)$query['advert_image'];
        } else {
            $data['advert_image'] = [];
        }

        $error = $this->validateStoreForm($data);
        if (!empty($error)) {
            return $response->withJson(["code" => 1, "message" =>  "参数错误！", "data" => $error]);
        }
        $storeModel = new StoreModel();
        $status = $storeModel->uploadStore($this->appid, $store_id, $data);
        $this->_logger(json_encode($status, true));
        if (!$status) {
            return $response->withJson(["code" => 1, "message" =>  "编辑商店失败,联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "编辑商店成功！", "data" => $status]);
    }

    /**
     * 获得 商店信息
     * @param Request $request
     * @param Response $response
     */
    public function getStoreInfo(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $type = $query['type'];
        $value = $query['value'];
        if (!in_array($type, ['id', 'url'])) {
            return $response->withJson(["code" => 1, "message" =>  "参数错误！", "data" => []]);
        }
        $storeModel = new StoreModel();
        if ('id' === $type) {
            $storeInfo = $storeModel->getStoreInfo($this->appid, $value);
        } elseif ('url' == $type) {
            $storeInfo = $storeModel->getStoreInfoByUrl($this->appid, $value);
        }
        if (!$storeInfo) {
            return $response->withJson(["code" => 1, "message" => "没有该商店！", "data" => []]);
        }
        $advertList = $storeModel->getAdvert($this->appid, $storeInfo['store_id']);
        $storeInfo['advert_image'] = $advertList;
        unset($storeInfoConfig);
        return $response->withJson(["code" => 0, "message" =>  "获得商店信息成功！", "data" => ['info' => $storeInfo]]);
    }

    /**
     * 删除商店
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function deleteStore(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $store_ids = $query['store_ids'];
        $storeModel = new StoreModel();
        $status = $storeModel->deleteStore($this->appid, $store_ids);
        if (!$status) {
            return $response->withJson(["code" => 1, "message" =>  "删除商店失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "删除商店成功！", "data" => []]);
    }

    /**
     * 验证商店数据合法性
     * @param $data
     */
    protected function validateStoreForm($data)
    {
        $error = [];
        $groupCustomerModel = new CustomerGroupModel();
        $info = $groupCustomerModel->getCustomerGroupInfo($this->appid, $data['customer_group_id']);
        if (!$info) {
            $error['error_customer_group'] = '该会员等级不存在';
        }
        if (!in_array($data['store_type'], $this->store_types)) {
            $error['error_store_type'] = '商店类型不存在';
        }
        return $error;
    }

    /**
     * 获得城市列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCityList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $zone_id = (int)$query['zone_id'];
        $cityModel = new CityModel();
        $list = $cityModel->getCities($zone_id);
        return $response->withJson(["code" => 0, "message" => "获得城市列表成功！", "data" => ['list' => $list]]);
    }

    /**
     * 获得区域列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getZoneList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $country_id = (int)$query['country_id'];
        $zoneModel = new ZoneModel();
        $list = $zoneModel->getZonesByCountryId($country_id);
        return $response->withJson(["code" => 0, "message" =>  "获得城市列表成功！", "data" => ['list' => $list]]);
    }

    /**
     * 获得菜单
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getMenus(Request $request, Response $response)
    {
        $model = new MenuModel();
        $menus = $model->getMenus();

        return $response->withJson(["code" => 0, "message" =>  "获得菜单列表成功！", "data" => [
            'list'  => $menus
        ]]);
    }

    /**
     * 获得支付配置
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getPaymentSetting(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $type = $query['type'];
        $model = new PaymentConfigModel();
        $config = $model->getConfigByType($this->appid, $type);
        $config = array_column($config, 'value', 'key');
        return $response->withJson(["code" => 0, "message" =>  "获得配置成功！", "data" => [
            'config'  => $config
        ]]);
    }

    /**
     * 设置支付配置
     * @param Request $request
     * @param Response $response
     */
    public function setPaymentSetting(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $type = $query['type'];
        $data = [];
        switch ($type) {
            case 'wechat';
                foreach ($query['data'] as $key => $value) {
                    $tmp = [
                        'code'  => 'wechat',
                    ];
                    if (!in_array($key, ['mchid', 'key', 'status', 'authorization', 'is_https'])) {
                        continue;
                    }
                    $tmp['key'] = $key;
                    $tmp['value'] = $value;
                    $data[] = $tmp;
                }
                break;
            case 'alipay':
                foreach ($query['data'] as $key => $value) {
                    $tmp = [
                        'code'  => 'alipay',
                    ];
                    if (!in_array($key, ['partner', 'seller_id', 'private_key_path', 'ali_public_key_path', 'sign_type'])) {
                        continue;
                    }
                    $tmp['key'] = $key;
                    $tmp['value'] = $value;
                    $data[] = $tmp;
                }
                break;
            default:
                return $response->withJson(["code" => 1, "message" =>  "支付配置TYPE错误！", "data" => []]);
        }
        $model = new PaymentConfigModel();
        $model->setConfig($this->appid, $data);
        return $response->withJson(["code" => 0, "message" => "配置成功！", "data" => []]);
    }
} 