<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-13
 * Time: 下午2:19
 */

namespace App\Controllers;
use Lib\Model\Product\CategoryModel;
use \Psr\Http\Message\ServerRequestInterface as Request,
    \Psr\Http\Message\ResponseInterface as Response,
    \Lib\Model\Product\FilterModel,
    \Lib\Model\Product\OptionModel,
    \Lib\Model\Setting\StoreModel,
    \Lib\Model\Product\ProductModel,
    \Lib\Model\Product\ManufacturerModel;

class ProductController extends BaseController
{
    /**
     * 针对商店, 活动商品详细
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getProductInfoByStore(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $product_id = (int)$data['product_id'];
        $store_id = (int)$data['store_id'];
        $customer_group_id = (int)$data['customer_group_id'];
        if (empty($product_id)) {
            return $response->withJson(["code" => 1, "message" => "参数失败！", "data" => []]);
        }
        if (empty($customer_group_id)) {
            $customer_group_id = 1;
        }
        $model = new ProductModel();

        // 获得商品详细信息
        $productOptions = $model->getProductOptions($this->appid, $product_id);
        foreach ($productOptions as &$product_option) {
            if ($product_option['type'] == 'select' || $product_option['type'] == 'radio'
                || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                if (!isset($product_option['product_option_value']['option_value_id'])) {
                    $optionModel = new OptionModel();
                    foreach ($product_option['product_option_value'] as &$product_option_value) {
                        $infoOption = $optionModel->getOptionValue($this->appid, $product_option_value['option_value_id']);
                        $product_option_value['name'] = $infoOption['name'];
                        $product_option_value['image'] = $infoOption['image'];
                        $product_option_value['sort_order'] = $infoOption['sort_order'];
                    }
                    unset($product_option_value);
                }
            }
        }
        unset($product_option);
        $info = $model->getProductDetailByCustomerGroupId($this->appid, $store_id, $product_id, $customer_group_id);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" => "获得商品失败！", "data" => []]);
        }
        $info['options'] = $productOptions;
        $images = $model->getProductImages($this->appid, $product_id);
        $info['images'] = $images;
        return $response->withJson(["code" => 0, "message" => "获得商品成功！", "data" => ['info' => $info]]);

    }

    /**
     * 针对商店, 用户等级获得商品信息
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getProductsByStore(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $store_id = $data['store_id'];
        $start = $data['start'];
        $limit = $data['limit'];
        $sort = $data['sort'];
        $order = strtoupper($data['order']);
        $filter_category_id = (int)$data['filter_category_id'];
        $customer_group_id = (int)$data['customer_group_id'];
        $filter_name = $data['filter_name'];
        $sortArr = [
            'quantity'      => 'p.quantity',
            'name'          => 'p.name',
            'price'         => 'p.price',
            'sort_order'    => 'p.sort_order',
            'date_added'    => 'p.date_added'
        ];
        $model = new ProductModel();
        $filter = [
            'start'     => (int)$start,
            'limit'     => (int)$limit,
        ];
        if (array_key_exists($sort, $sortArr)) {
            $filter['sort'] = $sortArr[$sort];
            $filter['order'] = $order;
        }
        if (!empty($filter_category_id)) {
            $filter['filter_category_id'] = $filter_category_id;
        }
        if (!empty($filter_name)) {
            $filter['filter_name'] = $filter_name;
        }
        $data = $model->getProductsByCustomerGroupId($this->appid, $store_id, $customer_group_id, $filter);
        return $response->withJson(["code" => 0, "message" => "获得商品列表成功！", "data" => $data]);
    }


    /**
     * 获得商品基本信息
     */
    public function getProductInfo(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $product_id = (int)$query['product_id'];
        $productModel = new ProductModel();
        $info = $productModel->getProductInfo($this->appid, $product_id);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" => "获得商品数据失败！", "data" => []]);
        }
        $info['product_category'] = $productModel->getProductToCategory($this->appid, $product_id);
        $info['product_filter'] = $productModel->getProductFilter($this->appid, $product_id);
        $info['product_store'] = $productModel->getProductToStore($this->appid, $product_id);
        $info['product_related'] = $productModel->getProductRelated($this->appid, $product_id);
        $product_options_tmp = $productModel->getProductOptions($this->appid, $product_id);
        $product_options = [];
        foreach ($product_options_tmp as $product_option) {
            $product_option_value_data = [];
            if (isset($product_option['product_option_value'])) {
                foreach ($product_option['product_option_value'] as $product_option_value) {
                    $product_option_value_data[] = array(
                        'product_option_value_id' => $product_option_value['product_option_value_id'],
                        'option_value_id'         => $product_option_value['option_value_id'],
                        'quantity'                => $product_option_value['quantity'],
                        'subtract'                => $product_option_value['subtract'],
                        'price'                   => $product_option_value['price'],
                        'price_prefix'            => $product_option_value['price_prefix'],
                        'points'                  => $product_option_value['points'],
                        'points_prefix'           => $product_option_value['points_prefix'],
                    );
                }
            }
            $product_options[] = array(
                'product_option_id'    => $product_option['product_option_id'],
                'product_option_value' => $product_option_value_data,
                'option_id'            => $product_option['option_id'],
                'name'                 => $product_option['name'],
                'type'                 => $product_option['type'],
                'value'                => isset($product_option['value']) ? $product_option['value'] : '',
                'required'             => $product_option['required']
            );
        }
        $info['product_option'] = $product_options;
        $option_values = [];
        foreach ($product_options as $product_option) {
            if ($product_option['type'] == 'select' || $product_option['type'] == 'radio'
                || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                if (!isset($data['option_values'][$product_option['option_id']])) {
                    $optionModel = new OptionModel();
                    $option_values[$product_option['option_id']] = $optionModel->getOptionValues($this->appid, $product_option['option_id']);
                }
            }
        }
        $info['option_value'] = $option_values;
        $product_discounts_tmp = $productModel->getProductDiscounts($this->appid, $product_id);
        $product_discounts = [];
        foreach ($product_discounts_tmp as $product_discount) {
            $product_discounts[] = [
                'customer_group_id' => $product_discount['customer_group_id'],
                'quantity'          => $product_discount['quantity'],
                'priority'          => $product_discount['priority'],
                'price'             => $product_discount['price'],
                'date_start'        => ($product_discount['date_start'] != '0000-00-00') ? $product_discount['date_start'] : '',
                'date_end'          => ($product_discount['date_end'] != '0000-00-00') ? $product_discount['date_end'] : ''
            ];
        }
        $info['product_discount'] = $product_discounts;
        $product_specials_tmp = $productModel->getProductSpecials($this->appid, $product_id);
        $product_specials = [];
        foreach ($product_specials_tmp as $product_special) {
            $product_specials[] = array(
                'customer_group_id' => $product_special['customer_group_id'],
                'priority'          => $product_special['priority'],
                'price'             => $product_special['price'],
                'date_start'        => ($product_special['date_start'] != '0000-00-00') ? $product_special['date_start'] : '',
                'date_end'          => ($product_special['date_end'] != '0000-00-00') ? $product_special['date_end'] :  ''
            );
        }
        $info['product_special'] =  $product_specials;
        $info['product_image'] = $productModel->getProductImages($this->appid, $product_id);
        $info['product_reward'] = $productModel->getProductRewards($this->appid, $product_id);
	$info['manufacturer'] = '';
        if ($info['manufacturer_id']) {
            $model = new ManufacturerModel();
            $manufacturerInfo = $model->getManufacturerInfo($this->appid, $info['manufacturer_id']);
            if ($manufacturerInfo) {
                $info['manufacturer'] = $manufacturerInfo['name'];
            }
        }
        return $response->withJson(["code" => 0, "message" => "获得商品数据成功！", "data" => ['info' =>$info]]);
    }

    /**
     * 新增商品
     * @param Request $request
     * @param Response $response
     */
    public function addProduct(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $data = [
            'price'             => (float)$query['price'],
            'quantity'          => (int)$query['quantity'],
            'minimum'           => (int)$query['minimum'],
            'subtract'          => (int)$query['subtract'],
            'stock_status_id'   => (int)$query['stock_status_id'],
            'name'              => $query['name'],
            'meta_title'        => $query['meta_title'],
        ];
        $data['shipping'] = isset($query['shipping']) ? (int)$query['shipping'] : 0;
        $data['sku'] = isset($query['sku']) ? $query['sku'] : '';
        $data['date_available'] = isset($query['date_available']) && !empty($query['date_available'])
            ? $query['date_available'] : '0000-00-00';
        $data['status'] = isset($query['status']) ? (int)$query['status'] : 1;
        $data['sort_order'] = isset($query['sort_order']) ? (int)$query['sort_order'] : 0;
        $data['manufacturer_id'] = isset($query['manufacturer_id']) ? (int)$query['manufacturer_id'] : 0;
        $data['description'] = isset($query['description']) ? $query['description'] : '';
        $data['tag'] = isset($query['tag']) ? $query['tag'] : '';
        $data['meta_description'] = isset($query['meta_description']) ? $query['meta_description'] : '';
        $data['meta_keyword'] = isset($query['meta_keyword']) ? $query['meta_keyword'] : '';
        $data['image'] = isset($query['image']) ? $query['image'] : '';
        $data['product_store'] = isset($query['product_store']) ? $query['product_store'] : [];
        $data['product_option'] = isset($query['product_option']) ? $query['product_option'] : [];
        $data['product_discount'] = isset($query['product_discount']) ? $query['product_discount'] : [];
        $data['product_special'] = isset($query['product_special']) ? $query['product_special'] : [];
        $data['product_image'] = isset($query['product_image']) ? $query['product_image'] : [];
        $data['product_category'] = isset($query['product_category']) ? $query['product_category'] : [];
        $data['product_filter'] = isset($query['product_filter']) ? $query['product_filter'] : [];
        $data['product_reward'] = isset($query['product_reward']) ? $query['product_reward'] : [];
        $error = $this->validateProductForm($data);
        if ($error) {
            return $response->withJson(["code" => 1, "message" => "新增商品失败！", "data" => $error]);
        }
        $model = new ProductModel();
        $result = $model->addProduct($this->appid, $data);
        if (!$result) {
            return $response->withJson(["code" => 1, "message" => "新增商品失败，联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "新增商品成功！", "data" => []]);
    }

    /**
     * 编辑商品
     * @param Request $request
     * @param Response $response
     */
    public function updateProduct(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $product_id = $query['product_id'];
        $data = [
            'price'             => (float)$query['price'],
            'quantity'          => (int)$query['quantity'],
            'minimum'           => (int)$query['minimum'],
            'subtract'          => (int)$query['subtract'],
            'stock_status_id'   => (int)$query['stock_status_id'],
            'name'              => $query['name'],
            'meta_title'        => $query['meta_title'],
        ];
        $data['shipping'] = isset($query['shipping']) ? (int)$query['shipping'] : 0;
        $data['sku'] = isset($query['sku']) ? $query['sku'] : '';
        $data['date_available'] = isset($query['date_available']) && !empty($query['date_available'])
            ? $query['date_available'] : '0000-00-00';
        $data['manufacturer_id'] = isset($query['manufacturer_id']) ? (int)$query['manufacturer_id'] : 0;
        $data['status'] = isset($query['status']) ? (int)$query['status'] : 1;
        $data['sort_order'] = isset($query['sort_order']) ? (int)$query['sort_order'] : 0;
        $data['description'] = isset($query['description']) ? $query['description'] : '';
        $data['tag'] = isset($query['tag']) ? $query['tag'] : '';
        $data['meta_description'] = isset($query['meta_description']) ? $query['meta_description'] : '';
        $data['meta_keyword'] = isset($query['meta_keyword']) ? $query['meta_keyword'] : '';
        $data['image'] = isset($query['image']) ? $query['image'] : '';
        $data['product_store'] = isset($query['product_store']) ? $query['product_store'] : [];
        $data['product_option'] = isset($query['product_option']) ? $query['product_option'] : [];
        $data['product_discount'] = isset($query['product_discount']) ? $query['product_discount'] : [];
        $data['product_special'] = isset($query['product_special']) ? $query['product_special'] : [];
        $data['product_image'] = isset($query['product_image']) ? $query['product_image'] : [];
        $data['product_category'] = isset($query['product_category']) ? $query['product_category'] : [];
        $data['product_filter'] = isset($query['product_filter']) ? $query['product_filter'] : [];
        $data['product_reward'] = isset($query['product_reward']) ? $query['product_reward'] : [];
        $error = $this->validateProductForm($data);
        if ($error) {
            return $response->withJson(["code" => 1, "message" => "编辑商品失败！", "data" => $error]);
        }
        $model = new ProductModel();
        $result = $model->updateProduct($this->appid, $product_id, $data);
        if (!$result) {
            return $response->withJson(["code" => 1, "message" => "编辑商品失败，联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "编辑商品成功！", "data" => []]);
    }

    /**
     * 删除商品
     * @param Request $request
     * @param Response $response
     */
    public function deleteProduct(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $product_ids = $query['product_ids'];
        $model = new ProductModel();
        $result = $model->deleteProduct($this->appid, $product_ids);
        if (!$result) {
            return $response->withJson(["code" => 1, "message" => "删除商品失败，联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "删除商品成功！", "data" => []]);
    }

    /**
     * 表单验证
     * @param $data
     * @return array
     */
    protected function validateProductForm($data)
    {
        $error = [];
        if ((mb_strlen($data['name']) < 1) || (mb_strlen($data['name']) > 255)) {
            $error['error_name'] = "商品名称必须介于3-255字符之间！";
        }
        if ((mb_strlen($data['meta_title']) < 1) || (mb_strlen($data['meta_title']) > 255)) {
            $error['error_meta_title'] = "Meta Title名称必须介于3-255字符之间！";
        }
        if ($error && !isset($error['error_warning'])) {
            $error['error_warning'] = "警告: 存在错误，请检查！";
        }
        return $error;
    }

    /**
     * 获得商品列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getProductList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $filters = [];
        if (isset($query['filter_name']) || !empty($query['filter_name'])) {
            $filters['filter_name'] = $query['filter_name'];
        }
        if (isset($query['filter_price']) || !empty($query['filter_price'])) {
            $filters['filter_price'] = (float)$query['filter_price'];
        }
        if (isset($query['filter_quantity']) || !empty($query['filter_quantity'])) {
            $filters['filter_quantity'] = (int)$query['filter_quantity'];
        }
        if (isset($query['filter_product_id']) || !empty($query['filter_product_id'])) {
            $filters['filter_product_id'] = (int)$query['filter_product_id'];
        }
        if (isset($query['filter_product_ids']) || !empty($query['filter_product_ids'])) {
            $filters['filter_product_ids'] = $query['filter_product_ids'];
        }
        if (isset($query['filter_status'])) {
            $filters['filter_status'] = (int)$query['filter_status'];
        }
        if (isset($query['order'])) {
            $filters['order'] = $query['order'];
        }
        if (isset($query['sort'])) {
            $filters['sort'] = $query['sort'];
        }
        $start = $limit = 0;
        if (isset($query['start'])) {
            $start = (int)$query['start'];
        }
        if (isset($query['limit'])) {
            $limit = (int)$query['limit'];
        }
        $model = new ProductModel();
        $data = $model->getProductsFilter($this->appid, $filters, $start, $limit);
        if (isset($query['get_option']) || 1 == $query['get_option']) {
            foreach ($data['list'] as &$product) {
                $product['option_data'] = $model->getProductOptions($this->appid, $product['product_id']);
            }
            unset($product);
        }
        return $response->withJson(["code" => 0, "message" => "获得商品列表成功！", "data" => $data]);
    }

    /**
     * 删除选项
     * @param Request $request
     * @param Response $response
     * @return array
     */
    public function deleteOption(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $option_ids = $data['option_ids'];
        $error = $this->validateOptionDelete($data);
        if (empty($error)) {
            $model = new OptionModel();
            $res = $model->deleteOptions($this->appid, $option_ids);
            if (!$res) {
                return $response->withJson(["code" => 1, "message" =>  "删除失败，联系管理员！", "data" => []]);
            }
            return $response->withJson(["code" => 0, "message" => "删除成功！", "data" => []]);
        }
        return $response->withJson(["code" => 1, "message" => "删除失败！", "data" => $error]);
    }

    /**
     * 删除选项验证
     * @param $data
     * @return array
     */
    protected function validateOptionDelete($data)
    {
        $productModel = new ProductModel();
        $error = [];
        foreach ($data['option_ids'] as $option_id) {
            $product_total = $productModel->getTotalProductsByOptionId($this->appid, $option_id);
            if ($product_total) {
                $error['error_warning'] = sprintf("警告: 不能删除此选项值，该选项值已经被关联到了 %s 个商品！", $product_total);
            }
        }
        return $error;
    }

    /**
     * 新增选项
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function addOption(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $name           = $data['name'];
        $type           = $data['type'];
        $sort_order     = $data['sort_order'];
        $option_values  = isset($data['option_values']) ? $data['option_values'] : [];
        $error = $this->validateOptionForm($data);
        if (!empty($error)) {
            return $response->withJson(["code" => 1, "message" =>  "新增选项失败！", "data" => $error]);
        }
        $optionModel = new OptionModel();
        $result = $optionModel->addOption($this->appid, $name, $type, $sort_order, $option_values);
        if (!$result) {
            return $response->withJson(["code" => 1, "message" =>  "新增选项失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "新增选项成功！", "data" => []]);
    }

    /**
     * 编辑选项
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function updateOption(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $name           = $data['name'];
        $option_id      = $data['option_id'];
        $type           = $data['type'];
        $sort_order     = $data['sort_order'];
        $option_values  = isset($data['option_values']) ? $data['option_values'] : [];
        $error = $this->validateOptionForm($data);
        if (!empty($error)) {
            return $response->withJson(["code" => 1, "message" =>  "编辑选项失败11！", "data" => $error]);
        }
        $optionModel = new OptionModel();
        $result = $optionModel->updateOption($this->appid, $option_id, $name, $type, $sort_order, $option_values);
        if (!$result) {
            return $response->withJson(["code" => 1, "message" =>  "编辑选项失败1！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "编辑选项成功！", "data" => []]);
    }

    /**
     * 验证选项数据
     * @param $data
     * @return array
     */
    protected function validateOptionForm($data)
    {
        $error = [];
        if ((mb_strlen($data['name']) < 1) || (mb_strlen($data['name']) > 128)) {
            $error['error_name'] = '选项名称必须介于1-128字符之间';
        }
        if (($data['type'] == 'select' || $data['type'] == 'radio'
                || $data['type'] == 'checkbox') && !isset($data['option_values'])) {
            $error['error_warning'] = '警告: 选项值必填！';
        }
        if (isset($data['option_values'])) {
            foreach ($data['option_values'] as $option_value_id => $option_value) {
                if ((mb_strlen($option_value['option_value_name']) < 1) || (mb_strlen($option_value['option_value_name']) > 128)) {
                    $error['error_option_value'][$option_value_id] = '选项值必须介于1-128字符之间！';
                }
            }
        }
        return $error;
    }

    /**
     * 获得选项列表成功
     * @param Request $request
     * @param Response $response
     */
    public function getOptionList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $filter_data = [
            'limit'    => 0,
            'start'    => 0,
        ];
        if (isset($query['start'])) {
            $filter_data['start'] = (int)$query['start'];
        }
        if (isset($query['limit'])) {
            $filter_data['limit']  = (int)$query['limit'];
        }
        if (isset($query['filter_name']) && !empty($query['filter_name'])) {
            $filter_data['filter_name'] = (int)$query['filter_name'];
        }
        if (isset($query['filter_option_ids']) && !empty($query['filter_option_ids'])) {
            $filter_data['filter_option_ids'] = $query['filter_option_ids'];
        }
        $optionModel = new OptionModel();
        $result = $optionModel->getOptions($this->appid, $filter_data);
        if (isset($query['get_value']) && 1 == $query['get_value']) {
            foreach ($result['list'] as &$option) {
                $option_value_data = [];
                if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox'
                    || $option['type'] == 'image') {
                    $option_values = $optionModel->getOptionValues($this->appid, $option['option_id']);
                    foreach ($option_values as $option_value) {
                        $option_value_data[] = [
                            'option_value_id' => $option_value['option_value_id'],
                            'name' => strip_tags(html_entity_decode($option_value['name'], ENT_QUOTES, 'UTF-8')),
                            'image' => $option_value['image']
                        ];
                    }
                    $sort_order = [];
                    foreach ($option_value_data as $key => $value) {
                        $sort_order[$key] = $value['name'];
                    }
                    array_multisort($sort_order, SORT_ASC, $option_value_data);
                }
                $option['option_value'] = $option_value_data;
            }
            unset($option);
        }
        return $response->withJson(["code" => 0, "message" =>  "获得选项列表成功！", "data" => $result]);
    }

    /**
     * 获得选项信息
     * @param Request $request
     * @param Response $response
     */
    public function getOptionInfo(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $option_id = (int) $query['option_id'];
        $optionModel = new OptionModel();
        $result = $optionModel->getOptionInfo($this->appid, $option_id);
        if (!$result) {
            return $response->withJson(["code" => 1, "message" =>  "获得选项信息失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "获得选项信息成功！", "data" => ['info' => $result]]);
    }

    /**
     * 获得顶部菜单 显示 分类(包括产品)
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getStoreTopCategoryList(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $store_id = (int)$data['store_id'];
        $customer_group_id = (int)$data['customer_group_id'];
        $start = 0;
        $limit = (int)$data['product_limit'];
        $model = new CategoryModel;
        $list = $model->getStoreCategoryList($this->appid, $store_id, true);
        $filter = [
            'start'             => (int)$start,
            'limit'             => (int)$limit,
        ];
        $model = new ProductModel();
        foreach ($list as &$row) {
            $filter['filter_category_id'] = (int)$row['category_id'];
            if ($limit > 0) {
                $products = $model->getProductsByCustomerGroupId($this->appid, $store_id, $customer_group_id, $filter);
            } else {
                $products = [];
            }
            $row['products'] = $products;
        }
        unset($row);
        return $response->withJson(["code" => 0, "message" => "获得商店分类列表成功！",
            "data" => ['list' => $list]]);
    }

    /**
     * 获得商店分类列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getStoreCategoryList(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $parent_id = isset($data['parent_id']) ? (int)$data['parent_id'] : 0;
        $store_id = (int)$data['store_id'];
        $model = new CategoryModel;
        $list = $model->getStoreCategoryList($this->appid, $store_id, false,  $parent_id);
        return $response->withJson(["code" => 0, "message" => "获得商店分类列表成功！",
            "data" => ['list' => $list]]);
    }

    /**
     * 获得分类信息
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCategory(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $category_id = (int)$query['category_id'];
        $categoryModel = new CategoryModel();
        $info = $categoryModel->getCategory($this->appid, $category_id);

        if (!$info) {
            return $response->withJson(["code" => 1, "message" =>  "获得分类失败！", "data" => []]);
        }
        $get_store = (int)$query['get_store'];
        if ($get_store) {
            $store_ids = $categoryModel->getCategoryToStore($this->appid, $category_id);
            $info['store_ids'] = $store_ids;
        }
        $get_filter = (int)$query['get_filter'];
        if ($get_filter) {
            $filter_ids = $categoryModel->getCategoryToFilter($this->appid, $category_id);
            $info['filter_ids'] = $filter_ids;
        }
        return $response->withJson(["code" => 0, "message" =>  "获得分类成功！", "data" => ["info" => $info]]);
    }

    /**
     * 获得分类列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getCategoryList(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $filter_category_ids = $query['filter_category_ids'];
        $filter_name = $query['filter_name'];
        $sort = $query['sort'];
        $order = $query['order'];
        $start = (int)$query['start'];
        $limit = (int)$query['limit'];

        $categoryModel = new CategoryModel();
        $list = $categoryModel->getCategoryList($this->appid, $filter_name, $filter_category_ids, $sort, $order, $start, $limit);
        return $response->withJson(["code" => 0, "message" =>  "获得商品列表成功！", "data" => $list]);
    }

    /**
     * 新增分类
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function addCategory(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        // 验证数据合法性
        $error = $this->validateCategoryForm($data);
        if (!empty($error)) {
            return $response->withJson(["code" => 1, "message" =>  "参数错误！", "data" => $error]);
        }
        // 所选商店是否是本人拥有
        $storeModel = new StoreModel();
        $storeInfos = $storeModel->getStoreInfo($this->appid, $data['store_id']);
        if (count($storeInfos) !== count($data['store_id'])) {
            return $response->withJson(["code" => 1, "message" =>  "选择商店不正确！", "data" => []]);
        }

        // 所选筛选是否是本人拥有
        if (count($data['filter_id']) > 0) {
            $filterModel = new FilterModel();
            $filterInfos = $filterModel->getFilterInfo($this->appid, $data['filter_id']);

            if (count($filterInfos) != count($data['filter_id'])) {
                return $response->withJson(["code" => 1, "message" =>  "选择筛选条件不正确！", "data" => []]);
            }
        }

        if (count($filterInfos) !== count($data['filter_id'])) {
            return $response->withJson(["code" => 1, "message" =>  "选择筛选条件不正确！", "data" => []]);
        }
        $parentId = (int)$data['parent_id'];
        $categoryModel = new CategoryModel();
        if (0 != $parentId) {
            $info = $categoryModel->getCategoryInfo($this->appid, $parentId);
            if (!$info) {
                return $response->withJson(["code" => 1, "message" =>  "上级分类不存在！", "data" => []]);
            }
        }

        $categoryId = $categoryModel->addCategory($this->appid, $data);

        if (!$categoryId) {
            return $response->withJson(["code" => 1, "message" =>  "新增失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "新增成功！", "data" => ['category_id' => $categoryId]]);
    }

    /**
     * 编辑分类数据
     * @param Request $request
     * @param Response $response
     */
    public function editCategory(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        // 验证数据合法性
        $error = $this->validateCategoryForm($data);
        if (!empty($error)) {
            return $response->withJson(["code" => 1, "message" =>  "参数错误！", "data" => $error]);
        }
        // 所选商店是否是本人拥有
        $storeModel = new StoreModel();
        $storeInfos = $storeModel->getStoreInfo($this->appid, $data['store_id']);
        if (count($storeInfos) !== count($data['store_id'])) {
            return $response->withJson(["code" => 1, "message" =>  "选择商店不正确！", "data" => []]);
        }

        // 所选筛选是否是本人拥有
        if (count($data['filter_id']) > 0) {
            $filterModel = new FilterModel();
            $filterInfos = $filterModel->getFilterInfo($this->appid, $data['filter_id']);

            if (count($filterInfos) != count($data['filter_id'])) {
                return $response->withJson(["code" => 1, "message" =>  "选择筛选条件不正确！", "data" => [$filterInfos]]);
            }
        }
        $parentId = (int)$data['parent_id'];
        $categoryModel = new CategoryModel();
        if (0 != $parentId) {
            $info = $categoryModel->getCategoryInfo($this->appid, $parentId);
            if (!$info) {
                return $response->withJson(["code" => 1, "message" =>  "上级分类不存在！", "data" => []]);
            }
        }

        $categoryId = (int)$data['category_id'];
        $categoryModel = new CategoryModel();
        $info = $categoryModel->getCategoryInfo($this->appid, $categoryId);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" =>  "编辑分类不存在！", "data" => []]);
        }

        $categoryId = $categoryModel->updateCategory($this->appid, $data);

        if (!$categoryId) {
            return $response->withJson(["code" => 1, "message" =>  "编辑失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "编辑成功！", "data" => ['category_id' => $categoryId]]);
    }

    /**
     * 删除分类
     * @param Request $request
     * @param Response $response
     */
    public function deleteCategory(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $category_ids = $data['category_ids'];
        $categoryModel = new CategoryModel();
        $categoryModel->deleteCategory($this->appid, $category_ids);
        return $response->withJson(["code" => 0, "message" => "删除成功！", "data" => []]);
    }

    /**
     * 重构分类树结构
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function repairCategory(Request $request, Response $response)
    {
        $categoryModel = new CategoryModel();
        $categoryModel->repairCategory($this->appid, 0);
        return $response->withJson(["code" => 0, "message" => "重构成功！", "data" => []]);
    }

    /**
     * 验证表单
     * @return bool
     */
    protected function validateCategoryForm($data)
    {
        $error = [];
        if ((mb_strlen($data['name']) < 2)
            || (mb_strlen($data['name']) > 255)) {
            $error['error_name'] = "分类名称必须在2 至 255个字符之间！";
        }
        if ((mb_strlen($data['meta_title']) < 2)
            || (mb_strlen($data['meta_title']) > 255)) {
            $error['error_meta_title'] = "Meta Title 必须是3 至 255个字符之间！";
        }
        return $error;
    }

    /**
     * 获得分组信息成功
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getGroupInfo(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $group_id = $data['group_id'];
        $filterModel = new FilterModel();
        $info = $filterModel->getGroupInfo($this->appid, $group_id);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" => "没有分组信息！", "data" => []]);
        }
        $list = $filterModel->getFilterListByGroupId($this->appid, $group_id);
        $info['list'] = $list;
        return $response->withJson(["code" => 0, "message" => "获得分组信息成功！", "data" => ['info' => $info]]);
    }

    /**
     * 获得分组列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getGroupList(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $sort = $data['sort'];
        $order = $data['order'];
        $start = (int)$data['start'];
        $limit = (int)$data['limit'];
        $filterModel = new FilterModel();
        $list = $filterModel->getFilterGroups($this->appid, $sort, $order, $start, $limit);
        return $response->withJson(["code" => 0, "message" => "获得分组列表成功！", "data" => $list]);
    }
    /**
     * 新增筛选分组
     * @param Request $request
     * @param Response $response
     */
    public function addFilterGroup(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $group_name = $data['group_name'];
        $sort_order = (int)$data['sort_order'];
        $filters = $data['filters'];

        // 验证数据合法性
        $error = $this->validateFilterForm($data);
        if (!empty($error)) {
            return $response->withJson(["code" => 1, "message" => "参数错误！", "data" => $error]);
        }
        $filterModel = new FilterModel();
        $filter_group_id = $filterModel->addGroup($this->appid, $group_name, $sort_order, $filters);
        if (!$filter_group_id) {
            return $response->withJson(["code" => 1, "message" =>  "新增失败，联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "新增成功！", "data" => []]);
    }

    /**
     * 编辑筛选分组
     * @param Request $request
     * @param Response $response
     */
    public function editFilterGroup(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $group_id = (int)$data['group_id'];
        $group_name = $data['group_name'];
        $sort_order = (int)$data['sort_order'];
        $filters = $data['filters'];
        // 验证数据合法性
        $error = $this->validateFilterForm($data);
        if (!empty($error)) {
            return $response->withJson(["code" => 1, "message" => "参数错误！", "data" => $error]);
        }
        $filterModel = new FilterModel();
        if (!$filterModel->getGroupInfo($this->appid, $group_id)) {
            return $response->withJson(["code" => 1, "message" => "没有分组信息！", "data" => []]);
        }

        $status = $filterModel->updateGroup($this->appid, $group_id, $group_name, $sort_order, $filters);
        if (!$status) {
            return $response->withJson(["code" => 1, "message" =>  "编辑失败，联系管理员！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" =>  "编辑成功！", "data" => []]);
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function deleteFilterGroup(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $group_ids = $data['group_ids'];
        $filterModel = new FilterModel();
        $filterModel->deleteGroup($this->appid, $group_ids);
        return $response->withJson(["code" => 0, "message" => "删除成功！", "data" => []]);
    }


    /**
     * 获得筛选列表
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getFilterList(Request $request, Response $response)
    {
        $request = $request->getQueryParams();
        $filter_data = [
            'filter_name'   => $request['filter_name'],
            'filter_ids'    => $request['filter_ids'],
        ];
        if(!empty($request['start'])) {
            $filter_data['start'] = (int)$request['start'];
        }
        if(!empty($request['start'])) {
            $filter_data['limit'] = (int)$request['limit'];
        }

        $filterModel = new FilterModel();
        $list = $filterModel->getFilterList($this->appid, $filter_data);
        return $response->withJson(["code" => 0, "message" => "获得列表成功！", "data" => ['list' => $list]]);
    }
    /**
     * 验证赛选数据合法性
     * @return bool
     */
    protected function validateFilterForm($data)
    {
        $error = [];
        if (!isset($data['group_name']) || mb_strlen($data['group_name']) < 1 || mb_strlen($data['group_name']) > 64) {
            $error['error_group'] =" 筛选分组名称必须介于1-64字符之间！";
        }
        if (isset($data['filters'])) {
            foreach ($data['filters'] as $filter_id => $filter) {
                if (!isset($filter['filter_name']) || (mb_strlen($filter['filter_name']) < 1) || (mb_strlen($filter['filter_name']) > 64)) {
                    $error['error_filter'][$filter_id] = "筛选名称必须介于1-64字符之间！";
                }
            }
        }
        return $error;
    }

    /**
     * 获得库存状态名称
     * @param Request $request
     * @param Response $response
     */
    public function getStockStatus(Request $request, Response $response)
    {
        $model = new ProductModel();
        $list = $model->getStockStatus();
        return $response->withJson(["code" => 0, "message" => "获得商品列表成功！", "data" => ['list' => $list]]);
    }

    /**
     * 获得制造商/品牌列表
     * @param Request $request
     * @param Response $response
     */
    public function getManufacturerList(Request $request, Response $response)
    {
        $request = $request->getQueryParams();
        $filterData['filter_name'] = isset($request['filter_name']) ? $request['filter_name'] : '';
        if (isset($request['sort']) && !empty($request['sort'])) {
            $filterData['sort'] = $request['sort'];
        }
        if (isset($request['order'])) {
            $filterData['order'] = $request['order'];
        }
        if (isset($request['start'])) {
            $filterData['start'] = $request['start'];
        }
        if (isset($request['limit'])) {
            $filterData['limit'] = $request['limit'];
        }
        $model = new ManufacturerModel();
        $data = $model->getList($this->appid, $filterData);
        return $response->withJson(["code" => 0, "message" => "获得商品品牌,制造商成功！", "data" => $data]);
    }

    /**
     * 新增品牌+制造商
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function addManufacturer(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $model = new ManufacturerModel();
        $data = [
            'name'                  => $query['name'],
            'sort_order'            => (int)$query['sort_order'],
            'image'                 => isset($query['image']) ? $query['image'] : '',
            'manufacturer_store'    => isset($query['manufacturer_store']) ? $query['manufacturer_store'] : '',
        ];
        $status = $model->addManufacturer($this->appid, $data);
        if (!$status) {
            return $response->withJson(["code" => 1, "message" => "新增商品品牌,制造商失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "新增商品品牌,制造商成功！", "data" => []]);
    }

    /**
     * 获得商品品牌+制造商 信息
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getManufacturerInfo(Request $request, Response $response)
    {
        $request = $request->getQueryParams();
        $manufacturer_id = $request['manufacturer_id'];
        $model = new ManufacturerModel();
        $info = $model->getManufacturerInfo($this->appid, $manufacturer_id);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" => "获得商品品牌,制造商失败！", "data" => []]);
        }
        $info['manufacturer_store'] = $model->getManufacturerToStore($this->appid, $manufacturer_id);
        return $response->withJson(["code" => 0, "message" => "获得商品品牌,制造商成功！", "data" => ['info' => $info]]);
    }

    /**
     * 编辑品牌+制造商
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function updateManufacturer(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $manufacturer_id = $query['manufacturer_id'];
        $model = new ManufacturerModel();
        $data = [
            'name'                  => $query['name'],
            'sort_order'            => (int)$query['sort_order'],
            'image'                 => isset($query['image']) ? $query['image'] : '',
            'manufacturer_store'    => isset($query['manufacturer_store']) ? $query['manufacturer_store'] : '',
        ];
        $status = $model->updateManufacturer($this->appid, $manufacturer_id, $data);
        if (!$status) {
            return $response->withJson(["code" => 1, "message" => "编辑商品品牌,制造商失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "编辑商品品牌,制造商成功！", "data" => []]);
    }

    /**
     * 删除品牌+制造商
     * @param Request $request
     * @param Response $response
     */
    public function deleteManufacturer(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $manufacturer_ids = $query['manufacturer_ids'];
        $model = new ManufacturerModel();
        $status = $model->deleteManufacturer($this->appid, $manufacturer_ids);
        if (!$status) {
            return $response->withJson(["code" => 1, "message" => "删除商品品牌,制造商失败！", "data" => []]);
        }
        return $response->withJson(["code" => 0, "message" => "删除商品品牌,制造商成功！", "data" => []]);
    }
}
