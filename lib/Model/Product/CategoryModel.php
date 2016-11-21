<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-15
 * Time: 下午2:10
 */

namespace Lib\Model\Product;

use Lib\Model\BaseModel;

class CategoryModel extends BaseModel
{

    /**
     * 获得分类信息
     * @param $appid
     * @param $category_id
     * @return mixed
     */
    public function getCategory($appid, $category_id)
    {
        $sql = "SELECT DISTINCT *, (SELECT GROUP_CONCAT(c1.name ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;')
                FROM mcc_category_path cp
                LEFT JOIN mcc_category c1
                ON (cp.path_id = c1.category_id AND cp.category_id != cp.path_id)
                WHERE cp.category_id = c.category_id AND c1.appid = " . $appid . "
                GROUP BY cp.category_id) AS path
            FROM mcc_category c
            WHERE c.category_id = " . (int)$category_id . " AND c.appid = " . $appid;
        return $this->db->query($sql)->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * 分类所属商店
     * @param $category_id
     * @return mixed
     */
    public function getCategoryToStore($appid, $category_id)
    {
        $list = $this->db->select('mcc_category_to_store', '*', [
            'AND'   => [
                'appid'         => (int)$appid,
                'category_id'   => (int)$category_id
            ]
        ]);
        return array_column($list, 'store_id');
    }

    /**
     * 分类关联筛选
     * @param $category_id
     * @return array
     */
    public function getCategoryToFilter($appid, $category_id)
    {
        $list = $this->db->select('mcc_category_filter', '*', [
            'AND'   => [
                'appid'         => (int)$appid,
                'category_id'   => (int)$category_id,
            ]
        ]);
        return array_column($list, 'filter_id');
    }

    /**
     * 获得分类列表
     * @param $appid
     * @param $data
     * @return mixed
     */
    public function getCategoryList($appid, $filter_name, $filter_category_ids, $sort, $order, $start, $limit)
    {
        $count_sql = "SELECT count(1) AS count FROM `mcc_category` WHERE appid = " . $appid;

        $sql = "SELECT cp.category_id AS category_id,
            GROUP_CONCAT(c2.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name,
            c1.parent_id,
            c1.sort_order
            FROM mcc_category_path cp 
            LEFT JOIN mcc_category c1 ON (cp.category_id = c1.category_id AND c1.appid = " . $appid . ")
            LEFT JOIN mcc_category c2 ON (cp.path_id = c2.category_id AND c2.appid = " . $appid . ") 
            WHERE cp.appid = " . $appid ;
        if (!empty($filter_name)) {
            $count_sql .= "AND name LIKE '%" . $this->escape($filter_name) . "%'";
            $sql .= " AND c2.name LIKE '%" . $this->escape($filter_name) . "%'";
        }
        if (!empty($filter_category_ids)) {
            $count_sql .= " AND category_id in (" . implode(',', $filter_category_ids) . ")";
            $sql .= " AND c1.category_id in (" . implode(',', $filter_category_ids)  . ")";
        }
        $count = $this->db->query($count_sql)
            ->fetchColumn();
        if (0 == $count) {
            return ["count" => 0, "list" => []];
        }

        $sql .= " GROUP BY cp.category_id";
        $sort_data = [
            'name',
            'sort_order'
        ];
        if (!empty($sort) && in_array($sort, $sort_data)) {
            $sql .= " ORDER BY " . $sort;
        } else {
            $sql .= " ORDER BY sort_order";
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
     * @param $data
     * @return int
     */
    public function addCategory($appid, $data)
    {
        $db = $this->db;
        $category_id = 0;
        $db->action(function($db) use ($appid, $data, &$category_id) {
            $last_category_id = $db->insert('mcc_category', [
                '`appid`'               => (int)$appid,
                '`image`'               => $data['image'],
                '`parent_id`'           => (int)$data['parent_id'],
                '`top`'                 => (int)$data['top'],
                '`column`'              => (int)$data['column'],
                '`sort_order`'          => (int)$data['sort_order'],
                '`status`'              => (int)$data['status'],
                '`name`'                => $data['name'],
                '`description`'         => $data['description'],
                '`meta_title`'          => $data['meta_title'],
                '`meta_description`'    => $data['meta_description'],
                '`meta_keyword`'        => $data['meta_keyword'],
                '`date_added`'          => date('Y-m-d H:i:s', CURRENT_TIME),
                '`date_modified`'       => date('Y-m-d H:i:s', CURRENT_TIME),
            ]);

            if (!$last_category_id) {
                return false;
            }

            // 构建树结构
            $level = 0;
            $results = $db->select('mcc_category_path', '*', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$data['parent_id']
                ],
                'ORDER' => ['level' => 'ASC']
            ]);
            foreach ($results as $result) {
                $db->insert('mcc_category_path', [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$last_category_id,
                    'path_id'       => (int)$result['path_id'],
                    'level'         => $level,
                ]);
                $level++;
            }
            $db->insert('mcc_category_path', [
                'appid'         => (int)$appid,
                'category_id'   => (int)$last_category_id,
                'path_id'       => (int)$last_category_id,
                'level'         => $level,
            ]);

            if (isset($data['filter_id'])) {
                foreach ($data['filter_id'] as $filter_id) {
                    $db->insert('mcc_category_filter', [
                        'appid'         => (int)$appid,
                        'category_id'   => (int)$last_category_id,
                        'filter_id'     => (int)$filter_id,
                    ]);
                }
            }
            if (isset($data['store_id'])) {
                foreach ($data['store_id'] as $store_id) {
                    $db->insert('mcc_category_to_store', [
                        'appid'         => (int)$appid,
                        'category_id'   => (int)$last_category_id,
                        'store_id'      => (int)$store_id,
                    ]);
                }
            }
            $category_id = $last_category_id;
            return true;
        });
        return $category_id;
    }

    /**
     * 编辑分类
     * @param $appid
     * @param $data
     */
    public function updateCategory($appid, $data)
    {
        $db = $this->db;
        $db->action(function($db) use ($appid, $data) {
            $db->update('mcc_category', [
                'image'             => $data['image'],
                'parent_id'         => (int)$data['parent_id'],
                'top'               => (int)$data['top'],
                'column'            => (int)$data['column'],
                'sort_order'        => (int)$data['sort_order'],
                'status'            => (int)$data['status'],
                'name'              => $data['name'],
                'description'       => $data['description'],
                'meta_title'        => $data['meta_title'],
                'meta_description'  => $data['meta_description'],
                'meta_keyword'      => $data['meta_keyword'],
                'date_modified'     => date('Y-m-d H:i:s', CURRENT_TIME),
            ], [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$data['category_id'],
                ]
            ]);
            // 构建树结构
            $rows = $db->select('mcc_category_path', '*', [
                'AND'       => [
                    'appid'     => (int)$appid,
                    'path_id'   => (int)$data['category_id'],
                ],
                'ORDER'     => ['level' => 'ASC']
            ]);
            if ($rows) {
                foreach ($rows as $category_path) {
                    $db->delete('mcc_category_path', [
                        'AND'   => [
                            'appid'         => (int)$appid,
                            'category_id'   => (int)$category_path['category_id'],
                            'level[<]'      => (int)$category_path['level']
                        ]
                    ]);
                    $path = [];
                    $categoryPaths = $db->select('mcc_category_path', '*', [
                        'AND'   => [
                            'appid'         => (int)$appid,
                            "category_id"   => (int)$data['parent_id'],
                        ],
                        "ORDER"         => ['level' => 'ASC']
                    ]);
                    foreach ($categoryPaths as $result) {
                        $path[] = $result['path_id'];
                    }
                    $categoryPaths = $db->select('mcc_category_path', '*', [
                        'AND'   => [
                            'appid'         => (int)$appid,
                            "category_id"   => (int)$category_path['category_id'],
                        ],
                        "ORDER"         => ['level' => 'ASC']
                    ]);
                    foreach ($categoryPaths as $result) {
                        $path[] = $result['path_id'];
                    }
                    $level = 0;
                    foreach ($path as $path_id) {
                        $categoryPath = $db->get('mcc_category_path', '*', [
                            "AND"   => [
                                'appid'         => (int)$appid,
                                "category_id"   => (int)$data['category_id'],
                                "path_id"       => (int)$path_id
                            ]
                        ]);

                        if(!$categoryPath) {
                            $db->insert('mcc_category_path', [
                                'appid'         => (int)$appid,
                                'category_id'   => (int)$data['category_id'],
                                'path_id'       => (int)$path_id,
                                'level'         => (int)$level
                            ]);
                        }else {
                            $db->update('mcc_category_path', [
                                'level' => (int)$level
                            ], [
                                "AND"   => [
                                    'appid'         => (int)$appid,
                                    "category_id"   => (int)$data['category_id'],
                                    "path_id"       => (int)$path_id
                                ]
                            ]);
                        }
                        $level++;
                    }

                }
            } else {
                $db->delete('mcc_category_path', [
                    'AND'   => [
                        'appid'         => (int)$appid,
                        'category_id'   => (int)$data['category_id'],
                    ]
                ]);
                $level = 0;
                $categoryPaths = $db->select('mcc_category_path', '*', [
                    'AND'   => [
                        'appid'         => (int)$appid,
                        'category_id'   => (int)$data['parent_id'],
                    ],
                    'ORDER' => ['level' =>  'ASC']
                ]);
                foreach ($categoryPaths as $result) {
                    $db->insert('mcc_category_path', [
                        'appid'         => (int)$appid,
                        'category_id'   => (int)$data['category_id'],
                        'path_id'       => (int)$result['path_id'],
                        'level'         => (int)$level
                    ]);
                    $level++;
                }

                $categoryPath = $db->get('mcc_category_path', '*', [
                    "AND"   => [
                        'appid'         => (int)$appid,
                        "category_id"   => (int)$data['category_id'],
                        "path_id"       => (int)$data['category_id']
                    ]
                ]);
                if ($categoryPath) {
                    $db->update('mcc_category_path', [
                        'level' => (int)$level
                    ], [
                        "AND"   => [
                            'appid'         => (int)$appid,
                            "category_id"   => (int)$data['category_id'],
                            "path_id"       => (int)$data['category_id']
                        ]
                    ]);
                } else {
                    $db->insert('mcc_category_path', [
                        'appid'         => (int)$appid,
                        'category_id'   => (int)$data['category_id'],
                        'path_id'       => (int)$data['category_id'],
                        'level'         => (int)$level
                    ]);
                }
            }

            $db->delete('mcc_category_filter', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$data['category_id'],
                ]
            ]);

            if (isset($data['filter_id'])) {
                foreach ($data['filter_id'] as $filter_id) {
                    $db->insert('mcc_category_filter', [
                        'appid'         => (int)$appid,
                        'category_id'   => (int)$data['category_id'],
                        'filter_id'     => (int)$filter_id,
                    ]);
                }
            }

            $db->delete('mcc_category_to_store', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$data['category_id'],
                ]
            ]);

            if (isset($data['store_id'])) {
                foreach ($data['store_id'] as $store_id) {
                    $db->insert('mcc_category_to_store', [
                        'appid'         => (int)$appid,
                        'category_id'   => (int)(int)$data['category_id'],
                        'store_id'      => (int)$store_id,
                    ]);
                }
            }
            return true;
        });
        return true;
    }

    /**
     * 获得分类基本信息
     * @param $appid
     * @param $category_id
     * @return mixed
     */
    public function getCategoryInfo($appid, $category_id)
    {
        return $this->db->get('mcc_category', '*', [
            'AND'   => [
                'category_id'   => (int)$category_id,
                'appid'         => (int)$appid
            ]
        ]);
    }

    /**
     *
     * @param $appid
     * @param $category_id
     */
    public function deleteCategory($appid, $category_ids)
    {
        $db = $this->db;
        $db->action(function($db) use ($appid, $category_ids) {
            $db->delete('mcc_category_path', [
                'AND' => [
                    'appid' => (int)$appid,
                    'OR'    => [
                        'category_id'   => $category_ids,
                        'path_id'       => $category_ids
                    ]
                ],
            ]);
            $db->delete('mcc_category', [
                'AND' => [
                    'category_id'   => $category_ids,
                    'appid'         => (int)$appid
                ]
            ]);
            $db->delete('mcc_category_filter', [
                'AND'   => [
                    'appid' => (int)$appid,
                    'category_id'   => $category_ids,
                ]
            ]);
            $db->delete('mcc_category_to_store', [
                'AND'   => [
                    'appid' => (int)$appid,
                    'category_id'   => $category_ids,
                ]
            ]);
            $db->delete('mcc_product_to_category', [
                'AND'   => [
                    'appid' => (int)$appid,
                    'category_id'   => $category_ids,
                ]
            ]);
            return true;
        });
    }

    /**
     * 重构树形结构
     * @param $appid
     * @param int $parent_id
     */
    public function repairCategory($appid, $parent_id = 0)
    {
        $db = $this->db;
        $categories = $db->select('mcc_category', '*', [
            'AND'    => [
                'parent_id' => (int)$parent_id,
                'appid'     => (int)$appid
            ]
        ]);
        foreach ($categories as $category) {
            $db->delete('mcc_category_path', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$category['category_id']
                ]
            ]);

            $level = 0;
            $categoryPaths = $db->select('mcc_category_path', '*', [
                'AND'   => [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$parent_id,
                ],
                'ORDER'         => ['level' => 'ASC']
            ]);
            foreach ($categoryPaths as $result) {
                $db->insert('mcc_category_path', [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$category['category_id'],
                    'path_id'       => (int)$result['path_id'],
                    'level'         => (int)$level,
                ]);
                $level++;
            }
            $categoryPath = $db->get('mcc_category_path', '*', [
                'AND'   =>  [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$category['category_id'],
                    'path_id'       => (int)$category['category_id']
                ]
            ]);
            if ($categoryPath) {
                $db->update('mcc_category_path', [
                    'level' =>  (int)$level
                ], [
                    'AND'   =>  [
                        'appid'         => (int)$appid,
                        'category_id'   => (int)$category['category_id'],
                        'path_id'       => (int)$category['category_id']
                    ]
                ]);
            } else {
                $db->insert('mcc_category_path', [
                    'appid'         => (int)$appid,
                    'category_id'   => (int)$category['category_id'],
                    'path_id'       => (int)$category['category_id'],
                    'level'         => (int)$level,
                ]);
            }
            $this->repairCategory($appid, $category['category_id']);
        }
        return true;
    }

    /**
     * 获得商城拥有分类
     * @param $appid
     * @param $store_id
     * @param bool $istop
     * @param int $parent_id
     * @return mixed
     */
    public function getStoreCategoryList($appid, $store_id, $istop = false, $parent_id = 0)
    {
        $where = [
            'AND' => [
                'mcc_category.appid'                => (int)$appid,
                'mcc_category.parent_id'            => (int)$parent_id,
                'mcc_category.status'               => 1,
                'mcc_category_to_store.store_id'    => (int)$store_id,
                'mcc_category_to_store.appid'       => (int)$appid
            ]
        ];
        if ($istop) {
            $where['AND']['top'] = 1;
        }
        return $this->db->select('mcc_category', [
            '[>]mcc_category_to_store'  => [
                'category_id' => 'category_id'
            ]], [
                'mcc_category.category_id',
                'mcc_category.image',
                'mcc_category.meta_title',
                'mcc_category.name'
            ], $where);

    }
}