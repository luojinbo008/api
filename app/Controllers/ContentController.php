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

    /**
     * 获得博客分类信息
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getBlogCategory(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $blog_category_id = $data['blog_category_id'];
        $get_store = isset($data['get_store']) ? (int)$data['get_store'] : 0;
        $model = new BlogModel();
        $info = $model->getCategory($this->appid, $blog_category_id);
        if (!$info) {
            return $response->withJson([
                "code"      => 1,
                "message"   => "获得博客分类失败！",
                "data"      => [
                ]
            ]);
        }
        if ($get_store) {
            $store_ids = $model->getCategoryToStore($this->appid, $blog_category_id);
            $info['store_ids'] = $store_ids;
        }
        return $response->withJson([
            "code"      => 0,
            "message"   => "获得博客分类成功！",
            "data"      => [
                'info' => $info
            ]
        ]);
    }

    /**
     * 编辑博客分类
     * @param Request $request
     * @param Response $response
     */
    public function editBlogCategory(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $blog_category_id = (int)$query['blog_category_id'];
        $data = [];
        if (!empty($query['blog_category_name'])) {
            $data['blog_category_name'] = $query['blog_category_name'];
        }
        if (!empty($query['blog_category_meta_title'])) {
            $data['blog_category_meta_title'] = $query['blog_category_meta_title'];
        }
        if (!empty($query['blog_category_description'])) {
            $data['blog_category_description'] = $query['blog_category_description'];
        }
        if (!empty($query['blog_category_meta_description'])) {
            $data['blog_category_meta_description'] = $query['blog_category_meta_description'];
        }
        if (!empty($query['blog_category_meta_keyword'])) {
            $data['blog_category_meta_keyword'] = $query['blog_category_meta_keyword'];
        }
        if (!empty($query['parent_id'])) {
            $data['parent_id'] = $query['parent_id'];
        }
        if (!empty($query['image'])) {
            $data['image'] = $query['image'];
        }
        if (!empty($query['sort_order'])) {
            $data['sort_order'] = $query['sort_order'];
        }
        if (!empty($query['status'])) {
            $data['status'] = $query['status'];
        }
        $blog_category_store = [];
        if (!empty($query['blog_category_store'])) {
            $blog_category_store = $query['blog_category_store'];
        }

        $model = new BlogModel();
        $status = $model->updateCategory($this->appid, $blog_category_id, $data, $blog_category_store);

        if (!$status) {
            return $response->withJson([
                "code"      => 1,
                "message"   => "编辑博客分类失败！",
                "data"      => []
            ]);
        }
        return $response->withJson([
            "code"      => 0,
            "message"   => "编辑博客分类成功！",
            "data"      => []
        ]);
    }

    /**
     * 重构博客结构
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function repairBlogCategory(Request $request, Response $response)
    {
        $model = new BlogModel();
        $model->repairCategories($this->appid);
        return $response->withJson([
            "code"      => 0,
            "message"   => "重构博客分类结构成功！",
            "data"      => []
        ]);
    }

    /**
     * 删除博客分类
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function deleteBlogCategory(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $blog_category_ids = $data['blog_category_ids'];
        $model = new BlogModel();
        $model->deleteCategory($this->appid, $blog_category_ids);
        return $response->withJson([
            "code"      => 0,
            "message"   => "删除博客分类成功！",
            "data"      => []
        ]);
    }

    /**
     *
     * @param Request $request
     * @param Response $response
     */
    public function getBlogList(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $filter_blog_ids = isset($data['filter_blog_ids']) ? $data['filter_blog_ids'] : [];
        $filter_title = isset($data['filter_title']) ? $data['filter_title'] : '';
        $start = isset($data['start']) ? (int)$data['start'] : 0;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 0;
        $order = isset($data['order']) ? $data['order'] : '';
        $sort = isset($data['sort']) ? $data['sort'] : '';
        $filter_status = isset($data['filter_status']) ? (int)$data['filter_status'] : null;
        $model = new BlogModel();
        $info = $model->getBlogList($this->appid, $filter_blog_ids, $filter_status, $filter_title, $order, $sort, $start, $limit);
        return $response->withJson([
            "code"      => 0,
            "message"   => "获得博客列表成功！",
            "data"      => $info
        ]);
    }

    public function addBlog(Request $request, Response $response)
    {
        $query = $request->getParsedBody();
        $blog_category_id = isset($query['blog_category_id']) ? $query['blog_category_id'] : [];
        $created = isset($query['created']) ? $query['created'] : '0000-00-00';
        $status = isset($query['status']) ? (int)$query['status'] : 0;
        $user_id = (int)$query['user_id'];
        $hits = isset($query['hits']) ? (int)$query['hits'] : 0;
        $image = isset($query['image']) ? $query['image'] : '';
        $video_code = isset($query['video_code']) ? $query['video_code'] : '';
        $featured = isset($query['featured']) ? (int)$query['featured'] : 0;
        $sort_order = isset($query['sort_order']) ? (int)$query['sort_order'] : 0;
        $title = isset($query['title']) ? $query['title'] : '';
        $brief = isset($query['brief']) ? $query['brief'] : '';
        $description = isset($query['description']) ? $query['description'] : '';
        $meta_title = isset($query['meta_title']) ? $query['meta_title'] : '';
        $meta_keyword = isset($query['meta_keyword']) ? $query['meta_keyword'] : '';
        $meta_description = isset($query['meta_description']) ? $query['meta_description'] : '';
        $tag = isset($query['tag']) ? $query['tag'] : '';

        $model = new BlogModel();


    }
}