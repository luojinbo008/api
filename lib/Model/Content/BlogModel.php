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
            LEFT JOIN mcc_blog_category c1 ON (cp.blog_category_id = c1.blog_category_id) 
            LEFT JOIN mcc_blog_category c2 ON (cp.path_id = c2.blog_category_id) "
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
        if (isset($data['start']) || isset($data['limit'])) {

            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
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
                'appid' => (int)$appid,
                'parent_id' => (int)$parent_id,
                'sort_order' => (int)$sort_order,
                'status' => (int)$status,
                'image' => $image,
                'date_modified' => date('Y-m-d H:i:s', CURRENT_TIME),
                'date_added' => date('Y-m-d H:i:s', CURRENT_TIME),
                'name' => $this->escape($blog_category_name),
                'description' => $this->escape($blog_category_description),
                'meta_title' => $this->escape($blog_category_meta_title),
                'meta_description' => $this->escape($blog_category_meta_description),
                'meta_keyword' => $this->escape($blog_category_meta_keyword)
            ]);

            if (!$blog_category_id) {
                return false;
            }
            $level = 0;
            $rows = $db->select('mcc_blog_category_path', [
                'AND' => [
                    'appid' => (int)$appid,
                    'blog_category_id' => (int)$parent_id
                ],
                'ORDER' => [
                    'level' => 'ASC'
                ]
            ]);
            foreach ($rows as $result) {
                $db->insert('mcc_blog_category_path', [
                    'appid' => (int)$appid,
                    'blog_category_id' => (int)$blog_category_id,
                    'path_id' => (int)$result['path_id'],
                    'level' => (int)$level,
                ]);
                $level++;
            }
            $db->insert('mcc_blog_category_path', [
                'appid' => (int)$appid,
                'blog_category_id' => (int)$blog_category_id,
                'path_id' => (int)$blog_category_id,
                'level' => (int)$level,
            ]);

            if (!empty($blog_category_store)) {
                foreach ($blog_category_store as $store_id) {
                    $db->insert('mcc_blog_category_to_store', [
                        'appid' => (int)$appid,
                        'blog_category_id' => (int)$blog_category_id,
                        'store_id' => (int)$store_id,
                    ]);
                }
            }
            return true;
        });
        return $blog_category_id;
    }
}