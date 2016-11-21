<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 15-11-17
 * Time: 下午6:49
 */

namespace Lib\Wechat\Platform;
class RequestMessageData
{
    protected $xml;
    protected $msgData;//文本消息


    public function __construct($xml)
    {
       $this->xml = $xml;
    }

    public static function instance($xml)
    {
        return new RequestMessageData($xml);
    }

    /**
     * 获得微信过来文本数据
     */
    protected function getTextMsg($xml)
    {
        $this->msgData['MsgType'] = $xml->MsgType;
        $this->msgData['ToUserName'] = $xml->ToUserName;
        $this->msgData['FromUserName'] = $xml->FromUserName;
        $this->msgData['CreateTime'] = $xml->CreateTime;
        $this->msgData['Content'] = $xml->Content;
        $this->msgData['MsgId'] = $xml->MsgId;
    }
    /**
     * 获得微信过来图片数据
     */
    protected function getImageMsg($xml)
    {
        $this->msgData['ToUserName'] = $xml->ToUserName;
        $this->msgData['FromUserName'] = $xml->FromUserName;
        $this->msgData['CreateTime'] = $xml->CreateTime;
        $this->msgData['MsgType'] = $xml->MsgType;
        $this->msgData['PicUrl'] = $xml->PicUrl;
        $this->msgData['MediaId'] = $xml->MediaId;
        $this->msgData['MsgId'] = $xml->MsgId;
    }
    /**
     * 获得微信过来语言数据
     */
    protected function getVoiceMsg($xml)
    {
        $this->msgData['ToUserName'] = $xml->ToUserName;
        $this->msgData['FromUserName'] = $xml->FromUserName;
        $this->msgData['CreateTime'] = $xml->CreateTime;
        $this->msgData['MsgType'] = $xml->MsgType;
        $this->msgData['MediaId'] = $xml->MediaId;
        $this->msgData['Format'] = $xml->Format;
        $this->msgData['MsgID'] = $xml->MsgID;
    }
    /**
     * 获得微信过来视频数据
     */
    protected function getVideoMsg($xml)
    {
        $this->msgData['ToUserName'] = $xml->ToUserName;
        $this->msgData['FromUserName'] = $xml->FromUserName;
        $this->msgData['CreateTime'] = $xml->CreateTime;
        $this->msgData['MsgType'] = $xml->MsgType;
        $this->msgData['MediaId'] = $xml->MediaId;
        $this->msgData['ThumbMediaId'] = $xml->ThumbMediaId;
        $this->msgData['MsgId'] = $xml->MsgId;
    }
    /**
     * 获得微信过来小视屏数据
     */
    protected function getShortVideoMsg($xml)
    {
        $this->msgData['ToUserName'] = $xml->ToUserName;
        $this->msgData['FromUserName'] = $xml->FromUserName;
        $this->msgData['CreateTime'] = $xml->CreateTime;
        $this->msgData['MsgType'] = $xml->MsgType;
        $this->msgData['MediaId'] = $xml->MediaId;
        $this->msgData['ThumbMediaId'] = $xml->ThumbMediaId;
        $this->msgData['MsgId'] = $xml->MsgId;
    }
    /**
     * 获得微信过来地理位置数据
     */
    protected function getLocationMsg($xml)
    {
        $this->msgData['ToUserName'] = $xml->ToUserName;
        $this->msgData['FromUserName'] = $xml->FromUserName;
        $this->msgData['CreateTime'] = $xml->CreateTime;
        $this->msgData['MsgType'] = $xml->MsgType;
        $this->msgData['Location_X'] = $xml->Location_X;
        $this->msgData['Location_Y'] = $xml->Location_Y;
        $this->msgData['Scale'] = $xml->Scale;
        $this->msgData['Label'] = $xml->Label;
        $this->msgData['MsgId'] = $xml->MsgId;
    }
    /**
     * 获得微信过来链接数据
     */
    protected function getlinkMsg($xml)
    {
        $this->msgData['ToUserName'] = $xml->ToUserName;
        $this->msgData['FromUserName'] = $xml->FromUserName;
        $this->msgData['CreateTime'] = $xml->CreateTime;
        $this->msgData['MsgType'] = $xml->MsgType;
        $this->msgData['Title'] = $xml->Title;
        $this->msgData['Description'] = $xml->Description;
        $this->msgData['Url'] = $xml->Url;
        $this->msgData['MsgId'] = $xml->MsgId;
    }

    /**
     *
     * 后续开发事件————————
     * 事件处理
     * @param $xml
     */
    protected function getEventMsg($xml)
    {
        $this->msgData['ToUserName'] = $xml->ToUserName;
        $this->msgData['FromUserName'] = $xml->FromUserName;
        $this->msgData['CreateTime'] = $xml->CreateTime;
        $this->msgData['MsgType'] = $xml->MsgType;
        $this->msgData['Event'] = $xml->Event;
        $this->msgData['EventKey'] = $xml->EventKey;
    }

    public function get()
    {
        $msgType = $this->xml->MsgType;
        switch($msgType){
            case "text":
                self::getTextMsg($this->xml);
                break;
            case "image":
                self::getImageMsg($this->xml);
                break;
            case "voice":
                self::getVoiceMsg($this->xml);
                break;
            case "video":
                self::getVideoMsg($this->xml);
                break;
            case "shortvideo":
                self::getShortVideoMsg($this->xml);
                break;
            case "location":
                self::getLocationMsg($this->xml);
                break;
            case "link":
                self::getlinkMsg($this->xml);
                break;
            case "event":
                self::getEventMsg($this->xml);
                break;
            default:
                throw new \Exception('msgType ' . $msgType . ' is not defined!');
        }

        return $this->msgData;
    }
} 