<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-1
 * Time: 下午5:19
 */
namespace Lib\Model;
use Lib\Handler\DBHandler;

class BaseModel
{
    public $db;
    public $db_name = 'default';

    public function getDb()
    {
        return $this->db_name;
    }

    public function __construct()
    {
        $servername = $this->getDb();
        $this->db = DBHandler::init($servername);
    }

    /**
     * 保存数据库转义
     * @param $value
     * @return mixed|string
     */
    public function escape($value)
    {
        if(is_null($value) || empty($value)){
            return '';
        }
        return str_replace(["\\", "\0", "\n", "\r", "\x1a", "'", '"'],
            ["\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"'], $value);
    }
}