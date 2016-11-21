<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-3-31
 * Time: 下午5:37
 */
namespace App\Controllers;
use Slim\Container;

class BaseController
{
    public $appid = null;
    public $container = null;
    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        //接口访问日志
        if ($this->container->get('request')->isGet()) {
            $query = $this->container->get('request')->getQueryParams();
            $request = $this->container->get('request')->getUri();
        } else {
            $query = $this->container->get('request')->getParsedBody();
            $request = $this->container->get('request')->getBody();
        }
        $this->appid = $query['appid'];
        $this->_logger((string)$request);
    }

    /**
     * 写入日志文件
     * @param $msg
     */
    protected function _logger($msg)
    {
        $this->container->get("logger")->write("[" . get_class($this) . "]" . $msg);
    }

}