<?php
/**
 * Created by PhpStorm.
 * User: zhoutianliang
 * Date: 2016/2/19
 * Time: 16:54
 */

namespace Lib\Handler;


use Monolog\Logger,
    Monolog\Handler\StreamHandler;

class LoggerHandler
{
    /**
     * @var Logger|null
     */
    protected $log = null;

    static $init = null;
    /**
     * LoggerHandler constructor.
     */
    private function __construct()
    {
        $logName = LOG_PATH . php_sapi_name() . '-' . date('Y-m-d') . '.txt';
        $this->log = new Logger('npay');
        $this->log->pushHandler(new StreamHandler($logName, Logger::DEBUG));
    }

    /**
     * @return LoggerHandler|null
     */
    public static function init()
    {
        if (self::$init === null) {
            self::$init = new self();
        }
        return self::$init;
    }
    
    /**
     * @param $message
     * @param $level
     */
    public function write($message, $level = 100)
    {
        $this->log->log(Logger::getLevelName($level), $message);
    }
}