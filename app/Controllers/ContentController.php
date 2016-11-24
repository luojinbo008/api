<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/11/23
 * Time: 11:04
 */

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request,
    \Psr\Http\Message\ResponseInterface as Response,
    \Lib\Model\Content\BlogModel;

class ContentController extends BaseController
{
    /**
     * 博客列表
     * @param Request $request
     * @param Response $response
     */
    public function getBlogCategoryList(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $filter_name = isset($data['filter_name']) ? $data['filter_name'] : '';
        $sort = isset($data['sort']) ? $data['sort'] : '';
        $order = isset($data['order']) ? $data['order'] : '';
        $start = isset($data['start']) ? $data['start'] : '';
        $limit = isset($data['limit']) ? $data['limit'] : '';
        $model = new BlogModel();
        $data = $model->getCategoryList($this->appid, $filter_name, $sort, $order, $start, $limit);
        return $response->withJson([
            "code"      => 0,
            "message"   => "获得博客分类列表成功！",
            "data"      => $data
        ]);
    }
}