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

    /**
     * 新增博客分类
     * @param Request $request
     * @param Response $response
     */
    public function addBlogCategory(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $blog_category_name = $query['blog_category_name'];
        $blog_category_meta_title = $query['blog_category_meta_title'];
        $blog_category_description = isset($query['blog_category_description']) ? $query['blog_category_description'] : '';
        $blog_category_meta_description = isset($query['blog_category_meta_description']) ? $query['blog_category_meta_description'] : '';
        $blog_category_meta_keyword = isset($query['blog_category_meta_keyword']) ? $query['blog_category_meta_keyword'] : '';
        $parent_id = isset($query['parent_id']) ? $query['parent_id'] : 0;
        $blog_category_store = isset($query['blog_category_store']) ? $query['blog_category_store'] : [];
        $image = isset($query['image']) ? $query['image'] : '';
        $sort_order = isset($query['sort_order']) ? $query['sort_order'] : 0;
        $status = isset($query['status']) ? $query['status'] : 0;
        $model = new BlogModel();
        $category_id = $model->addCategory($this->appid, $blog_category_name, $blog_category_meta_title, $blog_category_description,
            $blog_category_meta_description, $blog_category_meta_keyword, $parent_id, $blog_category_store, $image, $sort_order, $status);
        if (!$category_id) {
            return $response->withJson([
                "code"      => 1,
                "message"   => "新增博客分类失败！",
                "data"      => []
            ]);
        }
        return $response->withJson([
            "code"      => 0,
            "message"   => "新增博客分类成功！",
            "data"      => [
                'category_id' => $category_id
            ]
        ]);
    }
}