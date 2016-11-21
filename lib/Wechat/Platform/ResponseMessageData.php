<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 15-11-17
 * Time: 下午6:49
 */

namespace Lib\Wechat\Platform;

class ResponseMessageData
{
    protected $responseMsg;
    public function __construct($msg)
    {
        $this->responseMsg = $msg;
    }

    public  static function instance($msg)
    {
        return new ResponseMessageData($msg);
    }

    /**
     * 获得回复微信文本数据
     *
     * ToUserName	是	接收方帐号（收到的OpenID）
     * FromUserName	是	开发者微信号
     * CreateTime	是	消息创建时间 （整型）
     * MsgType	是	text
     * Content	是	回复的消息内容（换行：在content中能够换行，微信客户端就支持换行显示）
     */
    protected function getTextMsg($content, $time)
    {
         $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>";
        return sprintf($textTpl, $this->responseMsg['FromUserName'],  $this->responseMsg['ToUserName'], $time, "text", $content);
    }
    /**
     * 获得回复图片消息
     *
     * ToUserName	是	接收方帐号（收到的OpenID）
     * FromUserName	是	开发者微信号
     * CreateTime	是	消息创建时间 （整型）
     * MsgType	是	image
     * MediaId	是	通过上传多媒体文件，得到的id。
     */
    protected function getImageMsg($mediaid, $time)
    {
        $imageTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Image>
                    <MediaId><![CDATA[%s]]></MediaId>
                    </Image>
                    </xml>";
        return sprintf($imageTpl, $this->responseMsg['FromUserName'],  $this->responseMsg['ToUserName'], $time, "image", $mediaid);
    }
    /**
     * 回复语音消息
     *
     * ToUserName	是	接收方帐号（收到的OpenID）
     * FromUserName	是	开发者微信号
     * CreateTime	是	消息创建时间戳 （整型）
     * MsgType	是	语音，voice
     * MediaId	是	通过上传多媒体文件，得到的id
     */
    protected function getVoiceMsg($mediaid, $time)
    {
        $imageTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <Voice>
        <MediaId><![CDATA[%s]]></MediaId>
        </Voice>
        </xml>";

        return sprintf($imageTpl, $this->responseMsg['FromUserName'],  $this->responseMsg['ToUserName'], $time, "voice", $mediaid);
    }
    /**
     * 回复视频消息
     *
     * ToUserName	是	接收方帐号（收到的OpenID）
     * FromUserName	是	开发者微信号
     * CreateTime	是	消息创建时间 （整型）
     * MsgType	是	video
     * MediaId	是	通过上传多媒体文件，得到的id
     * Title	否	视频消息的标题
     * Description	否	视频消息的描述
     */
    protected function getVideoMsg($mediaid, $time, $title = "", $description = "")
    {
        $imageTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Video>
                    <MediaId><![CDATA[%s]]></MediaId>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    </Video>
                    </xml>";
        return sprintf($imageTpl, $this->responseMsg['FromUserName'],  $this->responseMsg['ToUserName'], $time, "video", $mediaid, $title, $description);
    }
    /**
     * 回复音乐消息
     *
     * ToUserName	是	接收方帐号（收到的OpenID）
     * FromUserName	是	开发者微信号
     * CreateTime	是	消息创建时间 （整型）
     * MsgType	是	music
     * Title	否	音乐标题
     * Description	否	音乐描述
     * MusicURL	否	音乐链接
     * HQMusicUrl	否	高质量音乐链接，WIFI环境优先使用该链接播放音乐
     * ThumbMediaId	是	缩略图的媒体id，通过上传多媒体文件，得到的id
     */
    protected function getMusicMsg($thumbMediaid, $time, $title="", $description="", $musicUrl="", $hQMusicUrl="")
    {
        $imageTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Music>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <MusicUrl><![CDATA[%s]]></MusicUrl>
                    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                    <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
                    </Music>
                    </xml>";
        return sprintf($imageTpl, $this->responseMsg['FromUserName'],  $this->responseMsg['ToUserName'], $time, "music", $title, $description, $musicUrl, $hQMusicUrl, $thumbMediaid);
    }
    /**
        * 回复图文消息
        *
        * ToUserName	是	接收方帐号（收到的OpenID）
        * FromUserName	是	开发者微信号
        * CreateTime	是	消息创建时间 （整型）
        * MsgType	是	news
        * ArticleCount	是	图文消息个数，限制为10条以内
        * Articles	是	多条图文消息信息，默认第一个item为大图,注意，如果图文数超过10，则将会无响应
        * Title	否	图文消息标题
        * Description	否	图文消息描述
        * PicUrl	否	图片链接，支持JPG、PNG格式，较好的效果为大图360*200，小图200*200
        * Url	否	点击图文消息跳转链接
     */
    protected function getPicUrlMsg($articleCount, $articles, $time)
    {
        $item = "";
        foreach($articles as $val)
        {
            if(!isset($val['title']) || !isset($val['description']) || !isset($val['picUrl']) || !isset($val['url'])){
                throw new \Exception('responseMsg Type news item: error!');
            }
            $itemTpl = "<item>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                    </item>";
            $item .= sprintf($itemTpl, $val['title'], $val['description'], $val['picUrl'], $val['url']);
        }


        $imageTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <ArticleCount>%s</ArticleCount>
                    <Articles>
                    {$item}
                    </Articles>
                    </xml>";
        return sprintf($imageTpl, $this->responseMsg['FromUserName'],  $this->responseMsg['ToUserName'], $time, "news", $articleCount);
    }

    /**
     * 统一获得消息
     */
    public function getMsg($type, $params)
    {
        switch($type){
            case "text":
                if(!isset($params["content"])){
                    throw new \Exception('responseMsg Type ' . $type . ' : param [content=>something]!');
                }
                $msg = $this->getTextMsg($params["content"], CURRENT_TIME);
                break;
            case "image":
                if(!isset($params["mediaid"])){
                    throw new \Exception('responseMsg Type ' . $type . ' : param [mediaid=>something]');
                }
                $msg = $this->getImageMsg($params["mediaid"], CURRENT_TIME);
                break;
            case "voice":
                if(!isset($params["mediaid"])){
                    throw new \Exception('responseMsg Type ' . $type . ' : param [mediaid=>something]');
                }
                $msg = $this->getVoiceMsg($params["mediaid"], CURRENT_TIME);
                break;
            case "video":
                if(!isset($params["mediaid"])){
                    throw new \Exception('responseMsg Type ' . $type . ' : param [mediaid=>something]');
                }
                $title = isset($params["title"]) ? $params["title"] : "";
                $description = isset($params["description"]) ? $params["description"] : "";
                $msg = $this->getVideoMsg($params["mediaid"], CURRENT_TIME, $title, $description);
                break;
            case "music":
                if(!isset($params["thumbMediaId"])){
                    throw new \Exception('responseMsg Type ' . $type . ' : param  [thumbMediaId=>something]');
                }
                $title = isset($params["title"]) ? $params["title"] : "";
                $description = isset($params["description"]) ? $params["description"] : "";
                $musicUrl = isset($params["musicUrl"]) ? $params["musicUrl"] : "";
                $hQMusicUrl = isset($params["hQMusicUrl"]) ? $params["hQMusicUrl"] : "";
                $msg = $this->getMusicMsg($params["thumbMediaId"], CURRENT_TIME, $title, $description, $musicUrl, $hQMusicUrl);
                break;
            case "news":
                if(!isset($params["articleCount"]) || !isset($params["articles"])){
                    throw new \Exception('responseMsg Type ' . $type . ' : param [articleCount=>something, articles=>x]');
                }
                if($params["articleCount"] !== count($params["articles"])){
                    throw new \Exception('responseMsg Type ' . $type . ' : articleCount is error');
                }
                $msg = $this->getPicUrlMsg($params["articleCount"], $params["articles"], CURRENT_TIME);
                break;
            default:
                throw new \Exception('responseMsg Type ' . $type . 'is not defined!');
        }
        return $msg;
    }
} 