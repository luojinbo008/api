<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-1
 * Time: 下午5:28
 */

namespace Lib\Handler;
use \Lib\Environment\Config;

class DBHandler {

    public static $db;

    /**
     * 初始化
     * @param string $servername
     * @return mixed
     */
    public static function init($servername = 'default')
    {
        return self::getDB($servername);
    }

    /**
     * 获得db
     * @param $servername
     * @return mixed
     */
    public static function getDB($servername)
    {
        if (!isset(self::$db[$servername])) {
            $config = Config::getConfig('database', $servername);
            self::$db[$servername] = new \medoo($config);
        }
        return self::$db[$servername];
    }
} 