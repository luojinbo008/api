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

    /**
     * 更新博客分类
     * @param $appid
     * @param $blog_category_id
     * @param $data
     * @param $blog_category_store
     */
    public function updateCategory($appid, $blog_category_id, $data, $blog_category_store)
    {
        $db = $this->db;
        $data['date_modified'] = date('Y-m-d H:i:s', CURRENT_TIME);
        $db->action(function($db) use ($appid, $blog_category_id, $data, $blog_category_store) {
            if (!empty($data)) {
                $db->update('mcc_blog_category', $data, [
                    'AND' => [
                        'appid'             => (int)$appid,
                        'blog_category_id'  => (int)$blog_category_id,
                    ]
                ]);
            }
            $rows = $db->select('mcc_blog_category_path', '*', [
                'AND' => [
                    'appid'     => (int)$appid,
                    'path_id'   => (int)$blog_category_id
                ],
                'ORDER' => [
                    'level' => 'ASC'
                ]
            ]);
            if ($rows) {
                foreach ($rows as $blog_category_path) {
                    $db->delete('mcc_blog_category_path', [
                        'AND' => [
                            'appid'             => (int)$appid,
                            'blog_category_id'  => (int)$blog_category_path['blog_category_id'],
                            'level[<]'          => (int)$blog_category_path['level']
                        ],
                    ]);
                    $path = [];
                    $rows2 = $db->select('mcc_blog_category_path', '*', [
                        'AND' => [
                            'appid'             => (int)$appid,
                            'blog_category_id'  => (int)$data['parent_id'],
                        ],
                        'ORDER' => [
                            'level' => 'ASC'
                        ]
                    ]);
                    foreach ($rows2 as $result) {
                        $path[] = $result['path_id'];
                    }
                    $rows3 = $db->select('mcc_blog_category_path', '*', [
                        'AND' => [
                            'appid'             => (int)$appid,
                            'blog_category_id'  => (int)$blog_category_path['blog_category_id'],
                        ],
                        'ORDER' => [
                            'level' => 'ASC'
                        ]
                    ]);

                    foreach ($rows3 as $result) {
                        $path[] = $result['path_id'];
                    }

                    $level = 0;
                    foreach ($path as $path_id) {
                        $sql = "REPLACE INTO `mcc_blog_category_path` SET 
                        appid = '" . (int)$appid . "', 
                        blog_category_id = '" . (int)$blog_category_path['blog_category_id'] . "', 
                        `path_id` = '" . (int)$path_id . "', 
                        level = '" . (int)$level . "'";
                        $db->exec($sql);
                        $level++;
                    }
                }
            } else {
                $db->delete('mcc_blog_category_path', [
                    'AND' => [
                        'appid'             => (int)$appid,
                        'blog_category_id'  => (int)$blog_category_id ,
                    ],
                ]);

                $level = 0;
                $rows = $db->select('mcc_blog_category_path', '*', [
                    'AND' => [
                        'appid'             => (int)$appid,
                        'blog_category_id'  => (int)$data['parent_id']
                    ],
                    'ORDER' => [
                        'level' => 'ASC'
                    ]
                ]);
                foreach ($rows as $result) {
                    $db->insert('mcc_blog_category_path', [
                        'appid'             => (int)$appid,
                        'blog_category_id'  => (int)$blog_category_id,
                        'path'              => (int)$result['path_id'],
                        'level'             => (int)$level
                    ]);
                    $level++;
                }
                $sql = "REPLACE INTO `mcc_blog_category_path` SET 
                        appid = '" . (int)$appid . "', 
                        blog_category_id = '" . (int)$blog_category_id . "', 
                        `path_id` = '" . (int)$blog_category_id . "', 
                        level = '" . (int)$level . "'";
                $db->exec($sql);
            }
            $db->delete('mcc_blog_category_to_store', [
                'AND'   => [
                    'appid'             => (int)$appid,
                    'blog_category_id'  => (int)$blog_category_id
                ]
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
        return true;
    }

    /**
     * 重构分类
     * @param $appid
     * @param int $parent_id
     */
    public function repairCategories($appid, $parent_id = 0) {
        $rows = $this->db->select('mcc_blog_category', '*', [
            'AND'   => [
                'appid'     => (int)$appid,
                'parent_id' => (int)$parent_id
            ]
        ]);
        foreach ($rows as $blog_category) {
            $this->db->delete('mcc_blog_category_path', [
                'AND'   => [
                    'appid'             => (int)$appid,
                    'blog_category_id'  => (int)$blog_category['blog_category_id']
                ]
            ]);
            $level = 0;
            $rows2 = $this->db->select('mcc_blog_category_path', '*', [
                'AND'   => [
                    'appid'             => (int)$appid,
                    'blog_category_id'  => (int)$parent_id
                ],
                'ORDER' => [
                    'level' => 'ASC'
                ]
            ]);
            foreach ($rows2 as $result) {
                $this->db->insert('mcc_blog_category_path', [
                    'appid'             => (int)$appid,
                    'blog_category_id'  => (int)$blog_category['blog_category_id'],
                    'path_id'           => (int)$result['path_id'],
                    'level'             => (int)$level,
                ]);
                $level++;
            }
            $sql = "REPLACE INTO `mcc_blog_category_path` SET blog_category_id = '" . (int)$blog_category['blog_category_id']
                . "', `path_id` = '" . (int)$blog_category['blog_category_id']
                . "', level = '" . (int)$level
                . "', appid = '" . (int)$appid . "'";
            $this->db->exec($sql);
            $this->repairCategories($appid, $blog_category['blog_category_id']);
        }
    }

    /**
     * 删除分类
     * @param $appid
     * @param $blog_category_id
     */
    public function deleteCategory($appid, $blog_category_ids)
    {
        $db = $this->db;
        $db->action(function($db) use ($appid, $blog_category_ids) {
            $db->delete('mcc_blog_category_path', [
                'AND'   => [
                    'appid'             => (int)$appid,
                    'blog_category_id'  => $blog_category_ids
                ]
            ]);
            $rows = $db->select('mcc_blog_category_path', '*', [
                'AND'   => [
                    'path_id'   => $blog_category_ids,
                    'appid'     => (int)$appid
                ]
            ]);
            foreach ($rows as $result) {
                $this->deleteCategory($appid, $result['blog_category_id']);
            }
            $db->delete('mcc_blog_category', [
                'AND'   => [
                    'appid'             => (int)$appid,
                    'blog_category_id'  => $blog_category_ids
                ]
            ]);
            $db->delete('mcc_blog_category_to_store', [
                'AND'   => [
                    'appid'             => (int)$appid,
                    'blog_category_id'  => $blog_category_ids
                ]
            ]);
        });
    }

    /**
     * 获得博客列表
     * @param $appid
     * @param $filter_status
     * @param $filter_title
     * @param $order
     * @param $sort
     * @param $start
     * @param $limit
     * @return array
     */
    public function getBlogList($appid, $filter_blog_ids, $filter_status, $filter_title, $order, $sort, $start, $limit)
    {
        $where['AND'] = [
            'appid' => (int)$appid
        ];
        if (!empty($filter_title)) {
            $where['AND']['title[~]'] =  "%" . $this->escape($filter_title) . "%";
        }
        if (!empty($filter_blog_ids)) {
            $where['AND']['blog_id'] = $filter_blog_ids;
        }
        if (!is_null($filter_status)) {
            $where['AND']['status'] = (int)$filter_status;
        }
        $count = $this->db->count('mcc_blog', $where);
        if (!$count) {
            return ['count' => 0, 'list' => []];
        }
        $where['GROUP'] = "blog_id";
        $sort_data = [
            'title',
            'status',
            'sort_order'
        ];

        if (!empty($order) && ($order == 'DESC')) {
            $orderBy = "DESC";
        } else {
            $orderBy = "ASC";
        }

        if (!empty($sort) && in_array($sort, $sort_data)) {
            $where['ORDER'][$sort] = $orderBy;
        } else {
            $where['ORDER']['title'] = 'ASC';
        }
        if (!empty($start) || !empty($limit)) {
            if ($start < 0) {
                $start = 0;
            }
            if ($limit < 1) {
                $limit = 20;
            }
            $where['LIMIT'] = [$start, $limit];
        }
        $blogList = $this->db->select('mcc_blog', '*', $where);
        return ['count' => $count, 'list' => $blogList];
    }


    public function addBlog($appid, $title, $meta_title, $brief, $description, $meta_keyword, $meta_description,
                $user_id, $hits, $image, $video_code, $featured, $created, $status, $sort_order, $tag, $blog_store = [],
                $blog_blog_category = [], $product_related = [], $blog_related = [])
    {
        $db = $this->db;
        $blog_id = 0;
        $db->action(function($db) use ($appid, $title, $meta_title, $brief, $description, $meta_keyword, $meta_description,
            $user_id, $hits, $image, $video_code, $featured, $created, $status, $sort_order, $tag, $blog_store,
            $blog_blog_category, $blog_related, &$blog_id) {
            $blog_id = $db->insert('mcc_blog', [
                'appid'             => (int)$appid,
                'title'             => $title,
                'meta_title'        => $meta_title,
                'meta_description'  => $meta_description,
                'meta_keyword'      => $meta_keyword,
                'tag'               => $tag,
                'brief'             => (int)$brief,
                'description'       => $description,
                'featured'          => $featured,
                'hits'              => (int)$hits,
                'created'           => $created,
                'video_code'        => $video_code,
                'user_id'           => (int)$user_id,
                'status'            => (int)$status,
                'sort_order'        => (int)$sort_order,
                'image'             => $image,
                'date_added'        => date('Y-m-d H:i:s', CURRENT_TIME),
                'date_modified'     => date('Y-m-d H:i:s', CURRENT_TIME),
            ]);
            if (!$blog_id) {
                return false;
            }
            if (!empty($blog_store)) {
                foreach ($blog_store as $store_id) {
                    $db->insert('mcc_blog_to_store', [
                        'appid' => $appid,
                        'store_id' => $store_id,
                        'blog_id' => $blog_id,
                    ]);
                }
            }
            if (!empty($blog_blog_category)) {
                foreach ($blog_blog_category as $blog_category_id) {
                    $db->insert('mcc_blog_to_blog_category', [
                        'appid' => $appid,
                        'blog_id' => $blog_id,
                        'blog_category_id' => $blog_category_id,
                    ]);
                }
            }
            if (!empty($product_related)) {
                $db->delete('mcc_blog_product', [
                    'AND' => [
                        'appid' => (int)$appid,
                        'blog_id' => (int)$blog_id
                    ]
                ]);
                foreach ($product_related as $related_id) {
                    $db->insert('mcc_blog_product', [
                        'appid' => (int)$appid,
                        'blog_id' => (int)$blog_id,
                        'related_id' => (int)$related_id,
                    ]);
                }
            }
            if (!empty($blog_related)) {
                $db->delete('mcc_blog_related', [
                    'AND' => [
                        'appid' => (int)$appid,
                        'blog_id' => (int)$blog_id
                    ]
                ]);
                foreach ($blog_related as $related_id) {
                    $db->insert('mcc_blog_related', [
                        'appid' => (int)$appid,
                        'blog_id' => (int)$blog_id,
                        'related_id' => (int)$related_id,
                    ]);
                }
                return true;
            }
        });
        return true;
    }

    /**
     * 删除博客
     * @param $appid
     * @param $blog_ids
     */
    public function deleteBlog($appid, $blog_ids)
    {

    }
}