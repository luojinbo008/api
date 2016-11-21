<?php
/**
 * Created by PhpStorm.
 * User: zhoutianliang
 * Date: 2016/2/19
 * Time: 16:54
 */
namespace Lib\Util;
class Util
{
    public static function ArrayColumn($input, $indexKey, $columnKey)
    {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array ();
        foreach((array)$input as $key => $row) {
            if($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) &&  ! empty($tmp)) ? current($tmp) : null;
            } else {
                if (strstr($columnKey, ',')) {
                    $field = explode(',',$columnKey);
                    $c = array();
                    foreach ((array)$field as $fv) {
                        $c[$fv] = isset($row [$fv]) ? $row [$fv] : null;
                    }
                    $tmp = $c;
                } else {
                    $tmp = isset($row [$columnKey]) ? $row [$columnKey] : null;
                }
            }
            if( ! $indexKeyIsNull) {
                if($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) &&  ! empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row [$indexKey]) ? $row [$indexKey] : 0;
                }
            }
            $result [$key] = $tmp;
        }
        return $result;
    }
    public static function gbkToUtf8($str)
    {
        if (!is_string($str)) {
            $str = strval($str);
        }
        return iconv('GBK', 'UTF-8', $str);
    }
    public static function utf8ToGbk($str)
    {
        if (!is_string($str)) {
            $str = strval($str);
        }
        return iconv('UTF-8', 'GBK', $str);
    }
    public static function getClientIp()
    {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return $ip;
    }
    public static function getGameRoom($gameid) 
    {
        $href = \Config::get('gameroom.' . $gameid);
        return $href && \Session::get('from') ? $href : 'javascript:void(0);';
    }
}
