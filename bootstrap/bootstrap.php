<?php
date_default_timezone_set('Asia/Shanghai');
define('APP_PATH', dirname(__DIR__));
define('APP_CONFIG', APP_PATH . '/config');
define('APP_ARGS', APP_PATH . '/app/Args');
define('LOG_PATH', APP_PATH . '/storage/logs/');
define('CURRENT_TIME', time());
define('CURRENT_TODAY', strtotime('today'));
define('ORDER_STATUS_START', '0');          // 开始
define('ORDER_STATUS_PAY', '1');            // 支付中
define('ORDER_STATUS_CANCEL', '2');         // 用户主动取消
define('ORDER_STATUS_END', '3');            // 订单结束（支付完成，第三方回调成功）
define('ORDER_STATUS_CLOSE', '4');          // 订单支付超时，系统关闭订单
define('ORDER_STATUS_COMMENT', '5');        // 用户已评价
define('ORDER_STATUS_REFUND_START', '6');   // 玩家发起退款申请
define('ORDER_STATUS_REFUND_END', '7');     // 管理员审核退款成功，并退款成功
define('ORDER_STATUS_REFUND_FAIL', '8');     // 管理员审核退款失败

$loader = require APP_PATH .'/vendor/autoload.php';

$loader->setPsr4('Lib\\', APP_PATH . '/lib');
$loader->setPsr4('App\\Controllers\\', APP_PATH . '/app/Controllers');
$c = new Slim\Container([
    'settings' => [
        'displayErrorDetails' => false,
    ],
]);
$c['logger'] = function() {
    return Lib\Handler\LoggerHandler::init();
};
use \Lib\Environment\Environment;
$env = Environment::Init(['dev' => ['BF*']])->getCurrentRunEnvironment();

$config = include APP_CONFIG . '/config.php';
$envConfig = APP_CONFIG . '/' . $env . '/config.php';
if (file_exists($envConfig)) {
    $cacheConfig = array_merge($config, require $envConfig);
}
$config['env'] = $env;

$c['cache'] = function($c) use ($config) {
    $cache = include APP_CONFIG . '/cache.php';
    $envCache = APP_CONFIG . '/' . $config['env'] . '/cache.php';
    if (file_exists($envCache)) {
        $cache = array_merge($cache, require $envCache);
    }
    $driver = $config['cache'];
    $object = null;
    switch ($driver) {
        case 'memcache':
            if (!class_exists('memcache')) {
                throw new \Exception('Please install memcache php extension.');
            }
            $object = new \Memcache();
            $object->addserver($cache['memcache']['host'], $cache['memcache']['port']);
            break;
        case 'memcached':
            if (!class_exists('memcached')) {
                throw new \Exception('Please install memcached php extension.');
            }
            $object = new \Memcached();
            if (count($object->getServerList()) === 0) {
                $object->setOption(Memcached::OPT_CONNECT_TIMEOUT, 10);
                $object->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
                $object->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 2);
                $object->setOption(Memcached::OPT_REMOVE_FAILED_SERVERS, true);
                $object->setOption(Memcached::OPT_RETRY_TIMEOUT, 1);
                $object->addServers($cache['memcached']['servers']);
            }
            break;
        case 'redis':
            if (!class_exists('redis')) {
                throw new \Exception('Please install redis php extension.');
            }
            $object = new \Redis();
            $object->connect($cache['redis']['host'], $cache['redis']['port'], $cache['timeout']);
            break;
        case 'file':
        default:
            $object = null;
    }
    return $object;
};

$c['errorHandler'] = function($c) {
    return function($request, $response, \Exception $e) use ($c) {
        $message = $e->getMessage();
       /* if ($request->isXhr()) {
            return $response->withJson(['code' => 500, 'message' => $message]);
        }*/
        return $response->withJson(['code' => 500, 'message' => $message]);
    };
};
$app = new Slim\App($c);
require APP_PATH . '/app/Http.php';
return $app;
