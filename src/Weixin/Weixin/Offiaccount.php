<?php

declare(strict_types=1);
/**
 * This file is part of Jiajushe.
 *
 * @link
 * @document
 * @contact
 * @license
 */

namespace Jiajushe\HyperfHelper\Weixin;

use Jiajushe\HyperfHelper\Exception\CustomError;
use Jiajushe\HyperfHelper\Exception\CustomNormal;
use Jiajushe\HyperfHelper\Helper\GuzzleHelper;
use Jiajushe\HyperfHelper\MongoDB\WeixinErrorLog;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Offiaccount
{
    public const URI_AUTH_ACCESS_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/access_token';//网页授权
    public const URI_AUTH_USERINFO = 'https://api.weixin.qq.com/sns/userinfo';//网页授权
    public const URI_MESSAGE_TEMPLATE = 'https://api.weixin.qq.com/cgi-bin/message/template/send';//发送模板消息
    public const URI_JSAPI_TICKET = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';//获得jsapi_ticket

    protected string $appid;

    protected string $secret;

    protected const WEIXIN_TYPE = 'OFFIACCOUNT';

    public function __construct(string $appid, string $secret)
    {
        $this->appid = $appid;
        $this->secret = $secret;
    }

    public function getRedisPrefix(): string
    {
        return Common::REDIS_PREFIX . self::WEIXIN_TYPE . ':';
    }

    /**
     * 公众号网页授权.
     * @param string $code
     * @return array
     * @throws CustomError
     */
    public function auth(string $code): array
    {
        $query = [
            'appid' => $this->appid,
            'secret' => $this->secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $method = 'get';
        $res = Common::request(self::WEIXIN_TYPE, $this->appid, $method, self::URI_AUTH_ACCESS_TOKEN, $query);
        $query = [
            'access_token' => $res['access_token'],
            'openid' => $res['openid'],
            'lang' => 'zh_CN',
        ];
        return Common::request(self::WEIXIN_TYPE, $this->appid, $method, self::URI_AUTH_USERINFO, $query);
    }

    /**
     * 发送模板消息.
     * @param array $data
     * [
     * "touser"=>"OPENID",(必须)
     * "template_id"=>"ngqIpbwh8bUfcSsECmogfXcV14J0tQlEpBO27izEYtY",(必须)
     * "url"=>"http://weixin.qq.com/download",
     * "miniprogram"=>[
     * "appid"=>"xiaochengxuappid12345",(必须)
     * "pagepath"=>"index?foo=bar"
     * ],
     * "data"=>[(必须)
     * "first"=> [
     * "value"=>"恭喜你购买成功！",(必须)
     * "color"=>"#173177"
     * ],
     * "keyword1"=>[
     * "value"=>"巧克力",(必须)
     * "color"=>"#173177"
     * ],
     * "remark"=>[
     * "value"=>"欢迎再次购买！",(必须)
     * "color"=>"#173177"
     * ]
     * ]
     * ]
     * @return bool
     * @throws CustomError
     */
    public function messageTemplate(array $data): bool
    {
        $method = 'post';
        $query = [
            'access_token' => Common::getAccessToken($this->appid, $this->secret, self::WEIXIN_TYPE),
        ];
        $options = [
            'json' => $data,
        ];
        Common::request(self::WEIXIN_TYPE, $this->appid, $method, self::URI_MESSAGE_TEMPLATE, $query, $options);
        return true;
    }

    /**
     * 获取分享配置.
     * @param string $url
     * @return array
     * @throws CustomError
     */
    public function shareConfig(string $url): array
    {
        $url = urldecode($url);
        $noncestr = Common::createNonceStr();
        $timestamp = time();
        $str = 'jsapi_ticket=' . $this->jsapiTicket() . '&noncestr=' . $noncestr . '&timestamp=' . $timestamp . '&url=' . $url;
        $signature = sha1($str);
        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'noncestr' => $noncestr,
            'appId' => $this->appid,
        ];
    }

    /**
     * 获取　jsapi_ticket
     * @return string
     * @throws CustomError
     */
    protected function jsapiTicket(): string
    {
        $redis_prefix = $this->getRedisPrefix() . 'JSAPI_TICKET:' . $this->appid;
        $redis = Common::getRedis();
        if ($jsapi_ticket = $redis->get($redis_prefix)) {
            return $jsapi_ticket;
        }
        $method = 'get';
        $query = [
            'access_token' => Common::getAccessToken($this->appid, $this->secret, self::WEIXIN_TYPE),
            'type' => 'jsapi',
        ];
        $res = Common::request(self::WEIXIN_TYPE, $this->appid, $method, self::URI_JSAPI_TICKET, $query);
        $redis->set($redis_prefix, $res['ticket'], ($res['expires_in'] - 600));
        return $res['ticket'];
    }
}
