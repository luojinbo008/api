<?php
//中间件
$app->add(function ($request, $response, $next) {
    // 签名验证
    if ($request->isGet()) {
        $query = $request->getQueryParams();
    } else {
        $query = $request->getParsedBody();
    }
    $path = $request->getUri()->getPath();
    list($a, $b) = explode('/', substr($path, 1));
    if (!in_array($a, ['notify']) && !(in_array($a, ['wechat']) && in_array($b, ['componentVerifyTicket', 'response', 'redirect']))) {
        if (empty($query) || empty($query['sign']) || empty($query['appid'])) {
            return $response->withJson([
                'message' => '对不起，接口签名参数验证失败', 'code' => 401, 'data' => []
            ], 200);
        }
        $querySign = base64_decode($query['sign']);
        $appId = $query['appid'];
        ksort($query);
        unset($query['sign']);
        unset($query['_url']);
        $appList = include APP_CONFIG . '/app.php';

        if (!isset($appList[$appId])) {
            return $response->withJson([
                'message' => '对不起，APPID不存在', 'code' => 402, 'data' => []
            ], 200);
        }
        $currentSign = md5(http_build_query($query) . $appList[$appId]['sign']);
        if ($querySign != $currentSign) {
            return $response->withJson([
                'message' => '对不起，接口签名验证失败', 'code' => 404, 'data' => []
            ], 200);
        }
        //参数验证
        $type = $request->getMethod();
        $argsList = include APP_ARGS . '/' . $a . '.php';
        if(empty($argsList)){
            return $response->withJson([
                'message' => '对不起，接口配置错误', 'code' => 404, 'data' => []
            ], 200);
        }

        $args = $argsList[$b]['args'];

        if(strtolower($argsList[$b]['type']) != strtolower($type)){
            return $response->withJson([
                'message' => '对不起，type错误！', 'code' => 404, 'data' => [$type]
            ], 200);
        }

        if (!empty($args)) {
            foreach ($args as $field => $type) {
                $typeList = explode('|', $type);
                $currentVar = isset($query[$field]) ? $query[$field] : null;
                foreach ($typeList as $value) {
                    switch ($value) {
                        case 'empty':
                            if (empty($currentVar) && 0 !== $currentVar && '0' !== $currentVar) {
                                return  $response->withJson([
                                    'message' => $field . '参数没有定义或者为空', 'code' => 406, 'data' => []
                                ]);
                            }
                            break;
                        case 'int':
                        case 'float':
                            if (!empty($currentVar) && !is_numeric($currentVar)) {
                                return  $response->withJson([
                                    'message' => $field . '参数必须是整数', 'code' => 406, 'data' => [is_int($currentVar)]
                                ]);
                            }
                            break;
                        case 'string':
                            if (!empty($currentVar) && !is_string($currentVar)) {
                                return  $response->withJson([
                                    'message' => $field . '参数必须是字符串', 'code' => 406, 'data' => []
                                ]);
                            }
                            break;
                        case 'array':
                            if (!empty($currentVar) && !is_array($currentVar)) {
                                return  $response->withJson([
                                    'message' => $field . '参数必须是数组', 'code' => 406, 'data' => [$currentVar]
                                ]);
                            }
                            break;
                    }
                }
            }
        }
    }
    $response = $next($request, $response);
    return $response;
});
require APP_PATH . "/app/Routes/product.php";
require APP_PATH . "/app/Routes/setting.php";
require APP_PATH . "/app/Routes/customer.php";
require APP_PATH . "/app/Routes/order.php";
require APP_PATH . "/app/Routes/wechat.php";
require APP_PATH . "/app/Routes/notify.php";