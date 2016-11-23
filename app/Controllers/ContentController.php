<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/11/23
 * Time: 11:04
 */

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request,
    \Psr\Http\Message\ResponseInterface as Response;

class ContentController extends BaseController
{
    /**
     * 博客列表
     * @param Request $request
     * @param Response $response
     */
    public function getBlogCategoryList(Request $request, Response $response)
    {
        return $response->withJson([
            "code"      => 0,
            "message"   => "获得博客分类列表成功！",
            "data"      => [
                'list' => []
            ]
        ]);
    }
}