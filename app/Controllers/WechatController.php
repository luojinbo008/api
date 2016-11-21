<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 2016/10/19
 * Time: 14:57
 */

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request,
    \Psr\Http\Message\ResponseInterface as Response,
    \Lib\Environment\Config,
    \Lib\Wechat\ThirdOpen\WXBizMsgCrypt,
    \Lib\Model\Setting\AppSignModel,
    \Lib\Model\Setting\WechatAuthorizationModel,
    \Slim\Container;

class WechatController extends BaseController
{
    public $config;

    /**
     * WechatController constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->config = Config::getConfig('wechat');
        parent::__construct($container);
    }

    /**
     * 获得授权方微信公众号配置
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function getConfig(Request $request, Response $response)
    {
        $model = new WechatAuthorizationModel();
        $info = $model->getInfo($this->appid);
        if (!$info) {
            return $response->withJson(["code" => 1, "message" => "公众号没有授权！", "data" => []]);
        }
        $info['component_appid'] = $this->config['appid'];
        $info['component_access_token'] = $this->getComponentAccessToken();
        return $response->withJson(["code" => 0, "message" => "获得授权公众号信息成功！", "data" => ['info' => $info]]);
    }

    /**
     * 获得授权地址
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function authorization(Request $request, Response $response)
    {
        $preAuthCode = $this->getPreAuthCode();
        if (is_array($preAuthCode)) {
            return $response->withJson($preAuthCode);
        }
        $model = new AppSignModel();
        $appInfo = $model->getAppSignInfo($this->appid);
        if (!$appInfo) {
            return $response->withJson(["code" => 1, "message" => "没有appid！", "data" => []]);
        }
        $url = sprintf("https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=%s&pre_auth_code=%s&redirect_uri=%s",
            $this->config['appid'], $preAuthCode, urlencode("http://" . $appInfo['host'] . "/backend/wechat/authorization"));
        $preAuthCodeCacheKey = 'WECHAT::PRE_AUTH_CODE_' . $this->appid;
        $this->container->get('cache')->delete ($preAuthCodeCacheKey);
        return $response->withJson(["code" => 0, "message" => "success！", "data" => ['url' => $url]]);
    }

    /**
     * 取消第三方授权
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function unAuthorization(Request $request, Response $response)
    {
        $model = new WechatAuthorizationModel();
        $model->deleteByAppId($this->appid);
        return $response->withJson(["code" => 0, "message" => "取消授权微信公众号成功！", "data" => []]);
    }

    /**
     * 获得第三方微信账号授权信息
     * @param Request $request
     * @param Response $response
     */
    public function getThirdWechatInfo(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $componentAccessToken = $this->getComponentAccessToken();
        $authCodeValue = isset($data['authCodeValue']) ? $data['authCodeValue'] : '';
        $model = new WechatAuthorizationModel();
        // 首次授权 + 重新授权
        if (!empty($authCodeValue)) {
            $url = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=" . $componentAccessToken;
            $res = $this->httpPostData($url, json_encode([
                "component_appid"           => $this->config['appid'],
                "authorization_code"        => $authCodeValue,
            ], true));
            if (200 !== $res[0]) {
                return $response->withJson(["code" => 1, "message" => "获得授权微信公众号信息通信失败【authorization_info】，联系管理员！", "data" => []]);
            }
            $result = json_decode($res[1], true);
            if (!isset($result['authorization_info'])) {
                return $response->withJson(["code" => 1, "message" => "获得授权微信公众号信息失败【authorization_info】，联系管理员！", "data" => []]);
            }
            $status = $model->modify($this->appid, $result['authorization_info']['authorizer_appid'], $result['authorization_info']['authorizer_refresh_token'],
                $result['authorization_info']['func_info']);
            if (!$status) {
                return $response->withJson(["code" => 1, "message" => "获得授权微信公众号信息失败，联系管理员！", "data" => []]);
            }
            $authorizer_appid = $result['authorization_info']['authorizer_appid'];

            if (!empty($result['authorization_info']['authorizer_access_token'])) {
                $authorizerAccessToken = $result['authorization_info']['authorizer_access_token'];
                $authorizerAccessTokenCacheKey = 'WECHAT::AUTHORIZER_ACCESS_TOKEN_' . $authorizer_appid;
                $expiresIn = $result['authorization_info']['expires_in'];
                $this->container->get('cache')->set($authorizerAccessTokenCacheKey, $authorizerAccessToken, $expiresIn - 60);
            }
        } else {
            $authorizerAccessToken = $this->getAuthorizerAccessToken($componentAccessToken);
            if (is_array($authorizerAccessToken) && isset($authorizerAccessToken['code'])) {
                return $response->withJson($authorizerAccessToken);
            }
            $authorizer_appid = $authorizerAccessToken['authorizer_appid'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=" . $componentAccessToken;
        $res = $this->httpPostData($url, json_encode([
            "component_appid"           => $this->config['appid'],
            "authorizer_appid"          => $authorizer_appid,
        ], true));
        if (200 !== $res[0]) {
            return $response->withJson(["code" => 1, "message" => "获得授权微信公众号信息失败，联系管理员！", "data" => []]);
        }
        $result = json_decode($res[1], true);
        $funcNames = [
                1   => "消息管理权限",
                2   => "用户管理权限",
                3   => "帐号服务权限",
                4   => "网页服务权限",
                5   => "微信小店权限",
                6   => "微信多客服权限",
                7   => "群发与通知权限",
                8   => "微信卡券权限",
                9   => "微信扫一扫权限",
                10  => "微信连WIFI权限",
                11  => "素材管理权限",
                12  => "微信摇周边权限",
                13  => "微信门店权限",
                14  => "微信支付权限",
                15  => "自定义菜单权限"
        ];
        foreach ($result['authorization_info']['func_info'] as &$funcInfo) {
            $funcInfo['funcscope_category']['name'] = $funcNames[$funcInfo['funcscope_category']['id']];
        }
        $serviceTypeInfo = [
            0 => '订阅号',//0代表订阅号
            1 => '订阅号',//历史老帐号升级后的订阅号
            2 => '服务号',//2代表服务号
        ];

        $verifyTypeInfo = [
            -1  => '未认证',//-1代表未认证
            0   => '微信认证',//0代表微信认证
            1   => '新浪微博认证',//1代表新浪微博认证
            2   => '腾讯微博认证',//2代表腾讯微博认证
            3   => '已资质认证通过但还未通过名称认证',//3代表已资质认证通过但还未通过名称认证
            4   => '已资质认证通过、还未通过名称认证，但通过了新浪微博认证',//4代表已资质认证通过、还未通过名称认证，但通过了新浪微博认证
            5   => '已资质认证通过、还未通过名称认证，但通过了腾讯微博认证',//5代表已资质认证通过、还未通过名称认证，但通过了腾讯微博认证
        ];
        $businessInfo = [
            'open_store'    => '微信门店功能',
            'open_scan'     => '微信扫商品功能',
            'open_pay'      => '微信支付功能',
            'open_card'     => '微信卡券功能',
            'open_shake'    => '微信摇一摇功能',
        ];
        $businessInfoTmp = [];
        foreach ($result['authorizer_info']['business_info'] as $key => $name) {
            $businessInfoTmp[] = [
                'name'      => $businessInfo[$key],
                'status'    => $name
            ];
        }
        $result['authorizer_info']['business_info'] = $businessInfoTmp;
        $result['authorizer_info']['service_type_info']['name'] = $serviceTypeInfo[$result['authorizer_info']['service_type_info']['id']];
        $result['authorizer_info']['verify_type_info']['name'] = $verifyTypeInfo[$result['authorizer_info']['verify_type_info']['id']];
        return $response->withJson(["code" => 0, "message" => "获得授权微信公众号信息成功！", "data" => ["info" => $result]]);
    }

    /**
     * 获得 authorizer_access_token
     * @param $componentAccessToken
     * @return array
     */
    protected function getAuthorizerAccessToken($componentAccessToken)
    {
        $model = new WechatAuthorizationModel();
        $info = $model->getInfo($this->appid);
        if (!$info) {
            return ["code" => 2, "message" => "请授权微信公众号API！", "data" => []];
        }
        $authorizerAccessTokenCacheKey = 'WECHAT::AUTHORIZER_ACCESS_TOKEN_' . $info['authorizer_appid'];
        $authorizerAccessToken = $this->container->get('cache')->get($authorizerAccessTokenCacheKey);

        if (!$authorizerAccessToken) {

            // 获取（刷新）授权公众号的接口调用凭据（令牌）
            $url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=" . $componentAccessToken;
            $res = $this->httpPostData($url, json_encode([
                "component_appid"           => $this->config['appid'],
                "authorizer_appid"          => $info['authorizer_appid'],
                "authorizer_refresh_token"  => $info['authorizer_refresh_token'],
            ], true));
            if (200 !== $res[0]) {
                return ["code" => 1, "message" => "获得授权公众号的接口调用凭据失败，联系管理员！", "data" => []];
            }
            $result = json_decode($res[1], true);
            $authorizerAccessToken = $result['authorizer_access_token'];
            $expiresIn = $result['expires_in'];
            $this->container->get('cache')->set($authorizerAccessTokenCacheKey, $authorizerAccessToken, $expiresIn - 60);
        }
        return [
            'authorizer_appid'      => $info['authorizer_appid'],
            'authorizerAccessToken' => $authorizerAccessToken
        ];
    }

    /**
     * 获得微信第三方ComponentAccessToken
     * @return array
     */
    protected function getComponentAccessToken()
    {
        $componentVerifyTicket = $this->container->get('cache')->get('WECHAT::COMPONENT_VERIFY_TICKET');
        if (!$componentVerifyTicket) {
            throw new \Exception("获得component_verify_ticket失败，联系管理员！");
        }
        $componentAccessTokenCacheKey = 'WECHAT::COMPONENT_ACCESS_TOKEN';
        $componentAccessToken = $this->container->get('cache')->get($componentAccessTokenCacheKey);
        if (!$componentAccessToken) {
            $url = "https://api.weixin.qq.com/cgi-bin/component/api_component_token";
            $res = $this->httpPostData($url, json_encode([
                "component_appid"           => $this->config['appid'],
                "component_appsecret"       => $this->config['secret'],
                "component_verify_ticket"   => $componentVerifyTicket
            ], true));
            if (200 !== $res[0]) {
                throw new \Exception("获得component_access_token失败，联系管理员！");
            }
            $data = json_decode($res[1], true);
            if (isset($data['errcode'])) {
                throw new \Exception("获得component_access_token失败，联系管理员！");
            }
            $componentAccessToken = $data['component_access_token'];
            $expiresIn = $data['expires_in'];
            $this->container->get('cache')->set($componentAccessTokenCacheKey, $componentAccessToken, $expiresIn - 60);
        }
        return $componentAccessToken;
    }

    /**
     * 获得微信第三方PreAuthCode
     * @return array
     */
    protected function getPreAuthCode()
    {
        $componentAccessToken = $this->getComponentAccessToken();
        $preAuthCodeCacheKey = 'WECHAT::PRE_AUTH_CODE_' . $this->appid;
        $preAuthCode = $this->container->get('cache')->get($preAuthCodeCacheKey);
        if (!$preAuthCode) {
            $url = "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=" . $componentAccessToken;
            $res = $this->httpPostData($url, json_encode([
                "component_appid" => $this->config['appid'],
            ], true));
            if (200 !== $res[0]) {
                return ["code" => 1, "message" => "获得pre_auth_code失败，联系管理员！", "data" => []];
            }
            $data = json_decode($res[1], true);
            if (isset($data['errcode'])) {
                return ["code" => 1, "message" => "获得pre_auth_code失败，联系管理员！", "data" => []];
            }
            $preAuthCode = $data['pre_auth_code'];
            $expiresIn = $data['expires_in'];
            $this->container->get('cache')->set($preAuthCodeCacheKey, $preAuthCode, $expiresIn - 60);
        }
        return $preAuthCode;
    }

    /**
     * 第三方平台时间监听
     * @param Request $request
     * @param Response $response
     */
    public function componentVerifyTicket(Request $request, Response $response)
    {
        $getParams = $request->getQueryParams();
        $timeStamp   = $getParams['timestamp'];
        $nonce       = $getParams['nonce'];
        $encryptType = $getParams['encrypt_type'];
        $uri = $this->container->get('request')->getUri();
        $msgSign = $getParams['msg_signature'];
        $xml = $request->getBody();
        $config = Config::getConfig('wechat');
        $pc = new WXBizMsgCrypt($config['token'], $config['encodingAesKey'], $config['appid']);
        $encryptMsg = '';
        $errCode = $pc->decryptMsg($msgSign, $timeStamp, $nonce, $xml, $encryptMsg);
        if (0 == $errCode) {
            $xml = new \DOMDocument();
            $xml->loadXML($encryptMsg);
            $infoType = $xml->getElementsByTagName('InfoType')->item(0)->nodeValue;
            switch ($infoType) {
                case 'component_verify_ticket':
                    $componentVerifyTicket = $xml->getElementsByTagName('ComponentVerifyTicket')->item(0)->nodeValue;
                    $res = $this->container->get('cache')->set('WECHAT::COMPONENT_VERIFY_TICKET', $componentVerifyTicket, 610);
                    if ($res) {
                        return $response->getBody()->write('success');
                    }
                    break;
                case 'unauthorized':
                    $authorizerAppid = $xml->getElementsByTagName('AuthorizerAppid')->item(0)->nodeValue;
                    $model = new WechatAuthorizationModel();
                    if ($model->deleteByAuthorizerAppid($authorizerAppid)) {
                        return $response->getBody()->write('success');
                    }
                    break;
                case 'updateauthorized':
                    $componentAccessToken = $this->getComponentAccessToken();
                    $url = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=" . $componentAccessToken;
                    $authorizationCode = $xml->getElementsByTagName('AuthorizationCode')->item(0)->nodeValue;
                    $res = $this->httpPostData($url, json_encode([
                        "component_appid"           => $this->config['appid'],
                        "authorization_code"        => $authorizationCode,
                    ], true));
                    if (200 !== $res[0]) {
                        $this->_logger("获得授权微信公众号信息通信失败【authorization_info】！");
                    }
                    $result = json_decode($res[1], true);
                    if (!isset($result['authorization_info'])) {
                        $this->_logger("获得授权微信公众号信息失败【authorization_info】！");
                    }
                    $model = new WechatAuthorizationModel();
                    $status = $model->modifyByAuthorizerAppId($result['authorization_info']['authorizer_appid'], $result['authorization_info']['authorizer_refresh_token'],
                        $result['authorization_info']['func_info']);
                    if (!$status) {
                        $this->_logger($result['authorization_info']['authorizer_appid']. "重新授权， 更新授权微信公众号信息失败！");
                    }
                    break;
                case 'authorized':;
                    break;
                default:
                    return $response->getBody()->write('fail');
            }
            return $response->getBody()->write('success');
        }
        return $response->getBody()->write('fail');
    }

    /**
     * 微信消息监听
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function response(Request $request, Response $response, $args)
    {
        $getParams = $request->getQueryParams();
        $timeStamp   = $getParams['timestamp'];
        $nonce       = $getParams['nonce'];
        $encryptType = $getParams['encrypt_type'];
        $uri = $this->container->get('request')->getUri();
        $msgSign = $getParams['msg_signature'];
        $xml = $request->getBody();
        $config = Config::getConfig('wechat');
        $pc = new WXBizMsgCrypt($config['token'], $config['encodingAesKey'], $config['appid']);
        $encryptMsg = '';
        $errCode = $pc->decryptMsg($msgSign, $timeStamp, $nonce, $xml, $encryptMsg);
        if (0 == $errCode) {
            $msg = '';
            $authorizer_appid = $args['appid'];
            $model = new WechatAuthorizationModel();
            $authorizerInfo = $model->getInfoByAuthorizerAppid($authorizer_appid);
            if ($authorizerInfo) {
                $appid = $authorizerInfo['appid'];
                $obj = (array)simplexml_load_string($encryptMsg, 'SimpleXMLElement', LIBXML_NOCDATA);
                switch ($obj['MsgType']) {
                    case 'text':
                        $xml = \Lib\Wechat\Platform\ResponseMessageData::instance($obj)->getMsg('text', ["content" => "xasxashk"]);
                        $pc->encryptMsg($xml, CURRENT_TIME, $nonce, $msg);
                        break;
                }
                return $response->getBody()->write($msg);
            }
        }
    }

    /**
     * 用户同意授权回调 跳转
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function redirect(Request $request, Response $response, $args)
    {
        $path = $args['path'];
        $authorizer_appid = $args['appid'];
        $model = new WechatAuthorizationModel();
        $info = $model->getInfoByAuthorizerAppid($authorizer_appid);
        if (!$info) {
            return $response->getBody()->write("请在后台授权微信公众号！");
        }
        $appid = $info['appid'];
        $appSignModel = new AppSignModel();
        $appInfo = $appSignModel->getAppSignInfo($appid);
        $host = $appInfo['host'];
        $tmp = parse_url($path);
        $path = $tmp['path'];
        $query = $tmp['query'];
        if (!empty($query)) {
            $query .= '&' . http_build_query($request->getQueryParams());
        } else {
            $query = http_build_query($request->getQueryParams());
        }
        return $response->withHeader('Location', 'http://' . $host . '/' . $path
            . '?' . $query);
    }

    /**
     * 获取消息发送数据统计
     * type = 1 getupstreammsg 获取消息发送概况数据
     * type = 2 getupstreammsghour 获取消息分送分时数据
     * type = 3 getupstreammsgweek 获取消息发送周数据
     * type = 4 getupstreammsgmonth 获取消息发送月数据
     * type = 5 getupstreammsgdist 获取消息发送分布数据
     * type = 6 getupstreammsgdistweek 获取消息发送分布周数据
     * type = 7 getupstreammsgdistmonth 获取消息发送分布月数据
     * @param Request $request
     * @param Response $response
     * @return static
     */
    public function getUpStreamMsg(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $types = [
            1 => 'getupstreammsg',
            2 => 'getupstreammsghour',
            3 => 'getupstreammsgweek',
            4 => 'getupstreammsgmonth',
            5 => 'getupstreammsgdist',
            6 => 'getupstreammsgdistweek',
            7 => 'getupstreammsgdistmonth',
        ];
        $type = $data['type'];
        if (!isset($types[$type])) {
            return $response->withJson(["code" => 1, "message" => "参数错误！", "data" => []]);
        }
        $accessToken = $this->getAuthorizerAccessToken();

        return $response->withJson(["code" => 0, "message" => "获得授权微信公众号信息成功！", "data" => ["info" => $accessToken, $this->appid]]);
    }

    /**
     * @param type $url
     * @param type $method
     * @return type
     * @throws \Exception
     */
    protected function httpPostData($url, $dataString)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($dataString))
        );
        ob_start();
        curl_exec($ch);
        $returnContent = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return [$return_code, $returnContent];
    }
}