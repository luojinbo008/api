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
}