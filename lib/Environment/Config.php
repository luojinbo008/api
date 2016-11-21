<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-1
 * Time: 下午5:20
 */

namespace Lib\Environment;

class Config {
    /**
     * 获得配置文件
     * @param $filename
     * @param null $key
     * @param null $default
     * @return array|mixed|null
     */
    public static function getConfig($filename, $key = null, $default = null)
    {
        $env = self::getCurrentRunEnvironment();
        $configPath = APP_CONFIG . '/' . $env . '/' . $filename . '.php';
        $config = require APP_CONFIG . '/' . $filename . '.php';
        if(file_exists($configPath)) {
            $config = array_merge($config, require $configPath);
        }
        if (!empty($key)) {
            return isset($config[$key]) ? $config[$key] : $default;
        }
        return $config;
    }

    /**
     * 环境
     * @return mixed
     */
    public static function getCurrentRunEnvironment()
    {
        return Environment::Init(['dev' => ['BF*']])->getCurrentRunEnvironment();
    }
} 