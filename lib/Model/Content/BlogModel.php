<?php

/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/11/23
 * Time: 18:17
 */
namespace Lib\Model\Content;
use \Lib\Model\BaseModel;

class BlogModel extends BaseModel
{
    /**
     * 获得分类列表
     * @param $appid
     * @param $filter_name
     * @param $sort
     * @param $order
     * @param $start
     * @param $limit
     * @return array
     */
    public function getCategoryList($appid, $filter_name, $sort, $order, $start, $limit)
    {
        $count_sql = "SELECT COUNT(*) AS total FROM mcc_blog_category" . " WHERE appid = " . $appid;
        $sql = "SELECT cp.blog_category_id AS blog_category_id, 
            GROUP_CONCAT(c1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, 
            c1.parent_id, c1.sort_order FROM mcc_blog_category_path cp 
            LEFT JOIN mcc_blog_category c1 ON (cp.path_id = c1.blog_category_id) 
            LEFT JOIN mcc_blog_category c2 ON (cp.blog_category_id = c2.blog_category_id) "
            . " WHERE cp.appid = " . $appid;

        if (!empty($filter_name)) {
            $count_sql .= " AND name LIKE '" . $this->escape($filter_name) . "%'";
            $sql .= " AND c2.name LIKE '" . $this->escape($filter_name) . "%'";
        }
        $count = $this->db->query($count_sql)
            ->fetchColumn();

        if (0 == $count) {
            return ["count" => 0, "list" => []];
        }
        $sql .= " GROUP BY cp.blog_category_id";
        $sort_data = [
            'name',
            'sort_order'
        ];
        if (!empty($sort) && in_array($sort, $sort_data)) {
            $sql .= " ORDER BY " . $sort;
        } else {
            $sql .= " ORDER BY name";
        }
        if (!empty($order) && ($order == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        if (!empty($start) || !empty($limit)) {
            if ($start < 0) {
                $start = 0;
            }
            if ($limit < 1) {
                $limit = 20;
            }
            $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }
        $list = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return ["count" => $count, "list" => $list];
    }

    /**
     * 新增分类
     * @param $appid
     * @param $blog_category_name
     * @param $blog_category_meta_title
     * @param $blog_category_description
     * @param $blog_category_meta_description
     * @param $blog_category_meta_keyword
     * @param $parent_id
     * @param $blog_category_store
     * @param $image
     * @param $sort_order
     * @param $status
     * @return int
     */
    public function addCategory($appid, $blog_category_name, $blog_category_meta_title, $blog_category_description,
            $blog_category_meta_description, $blog_category_meta_keyword, $parent_id, $blog_category_store, $image, $sort_order, $status)
    {
        $db = $this->db;
        $blog_category_id = 0;
        $db->action(function ($db) use (
            $appid, $blog_category_name, $blog_category_meta_title, $blog_category_description,
            $blog_category_meta_description, $blog_category_meta_keyword, $parent_id, $blog_category_store, $image,
            $sort_order, $status, &$blog_category_id
        ) {
            $blog_category_id = $db->insert('mcc_blog_category', [
                'appid'             => (int)$appid,
                'image'             => $image,
                'parent_id'         => (int)$parent_id,
                'sort_order'        => (int)$sort_order,
                'status'            => (int)$status,
                'date_modified'     => date('Y-m-d H:i:s', CURRENT_TIME),
                'date_added'        => date('Y-m-d H:i:s', CURRENT_TIME),
                'name'              => $this->escape($blog_category_name),
                'description'       => $this->escape($blog_category_description),
                'meta_title'        => $this->escape($blog_category_meta_title),
                'meta_description'  => $this->escape($blog_category_meta_description),
                'meta_keyword'      => $this->escape($blog_category_meta_keyword)
            ]);

            if (!$blog_category_id) {
                return false;
            }
            $level = 0;
            $rows = $db->select('mcc_blog_category_path', [
                'AND' => [
                    'appid'             => (int)$appid,
                    'blog_category_id'  => (int)$parent_id
                ],
                'ORDER' => [
                    'level' => 'ASC'
                ]
            ]);
            foreach ($rows as $result) {
                $db->insert('mcc_blog_category_path', [
                    'appid'             => (int)$appid,
                    'blog_category_id'  => (int)$blog_category_id,
                    'path_id'           => (int)$result['path_id'],
                    'level'             => (int)$level,
                ]);
                $level++;
            }
            $db->insert('mcc_blog_category_path', [
                'appid'             => (int)$appid,
                'blog_category_id'  => (int)$blog_category_id,
                'path_id'           => (int)$blog_category_id,
                'level'             => (int)$level,
            ]);

            if (!empty($blog_category_store)) {
                foreach ($blog_category_store as $store_id) {
                    $db->insert('mcc_blog_category_to_store', [
                        'appid'             => (int)$appid,
                        'blog_category_id'  => (int)$blog_category_id,
                        'store_id'          => (int)$store_id,
                    ]);
                }
            }
            return true;
        });
        return $blog_category_id;
    }


    /**
     * 获得分类信息
     * @param $appid
     * @param $blog_category_id
     * @return mixed
     */
    public function getCategory($appid, $blog_category_id)
    {
        $sql = "SELECT DISTINCT *, 
                  (
                      SELECT GROUP_CONCAT(c1.name ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') 
                      FROM mcc_blog_category_path cp 
			          LEFT JOIN mcc_blog_category c1 ON (cp.path_id = c1.blog_category_id AND cp.blog_category_id != cp.path_id)  
                      WHERE cp.path_id = c1.blog_category_id AND cp.path_id = c1.blog_category_id AND 
                      cp.blog_category_id != cp.path_id AND cp.blog_category_id = " . (int)$blog_category_id  . "
                      AND cp.appid = " . (int)$appid . "
                      GROUP BY cp.blog_category_id
                  ) AS path
                FROM mcc_blog_category c 
                LEFT JOIN mcc_blog_category c2 ON (c.blog_category_id = c2.blog_category_id AND c2.appid = " . (int)$appid . ") 
                WHERE c.blog_category_id = '" . (int)$blog_category_id . "' AND c.appid = " . $appid;
        $info = $this->db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $info;
    }

    /**
     * 博客分类所属商店
     * @param $category_id
     * @return mixed
     */
    public function getCategoryToStore($appid, $blog_category_id)
    {
        $list = $this->db->select('mcc_blog_category_to_store', '*', [
            'AND'   => [
                'appid'             => (int)$appid,
                'blog_category_id'  => (int)$blog_category_id
            ]
        ]);
        return array_column($list, 'store_id');
    }
}