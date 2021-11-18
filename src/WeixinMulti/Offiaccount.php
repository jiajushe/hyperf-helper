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
namespace Jiajushe\HyperfHelper\WeixinMulti;

use Jiajushe\HyperfHelper\Exception\CustomError;
use Jiajushe\HyperfHelper\Exception\CustomNormal;
use Jiajushe\HyperfHelper\Helper\GuzzleHelper;
use Jiajushe\HyperfHelper\MongoDB\WeixinErrorLog;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Offiaccount
{
    public const URI_ACCESS_TOKEN = 'https://api.weixin.qq.com/cgi-bin/token';

    public const URI_AUTH_ACCESS_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    public const URI_AUTH_USERINFO = 'https://api.weixin.qq.com/sns/userinfo';

    protected string $appid;

    protected string $secret;

    public function __construct(string $appid, string $secret)
    {
        $this->appid = $appid;
        $this->secret = $secret;
    }

    public function getRedisPrefix(): string
    {
        return Common::REDIS_PREFIX . 'OFFIACCOUNT:';
    }

    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws CustomError
     * @throws NotFoundExceptionInterface
     */
    public function getAccessToken(): string
    {
        $redis_prefix = $this->getRedisPrefix() . 'ACCOUNT_TOKEN:' . $this->appid;
        $redis = Common::getRedis();
        if ($access_token = $redis->get($redis_prefix)) {
            return $access_token;
        }
        $method = 'get';
        $query = [
            'appid' => $this->appid,
            'secret' => $this->secret,
            'grant_type' => 'client_credential',
        ];
        $guzzleHelper = new GuzzleHelper();
        $res = $guzzleHelper->getResponse($guzzleHelper->request($method, self::URI_ACCESS_TOKEN, $query));
        if (isset($res['errcode'])) {
            (new WeixinErrorLog())->log($this->appid, $method, self::URI_AUTH_ACCESS_TOKEN, $query, $res);
            throw new CustomError('获取access_token失败');
        }
        $redis->set($redis_prefix, $res['access_token'], ($res['expires_in'] - 600));
        return $res['access_token'];
    }

    /**
     * 公众号网页授权.
     * appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code.
     * https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN.
     * @throws CustomError
     * @throws CustomNormal
     */
    public function auth(string $code): array
    {
        $guzzleHelper = new GuzzleHelper();
        $query = [
            'appid' => $this->appid,
            'secret' => $this->secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $method = 'get';
        $res = $guzzleHelper->request($method, self::URI_AUTH_ACCESS_TOKEN, $query);
        $res = $guzzleHelper->getResponse($res);
        if (isset($res['errcode'])) {
            (new WeixinErrorLog())->log($this->appid, $method, self::URI_AUTH_ACCESS_TOKEN, $query, $res);
            throw new CustomNormal('微信授权失败');
        }
        $query = [
            'access_token' => $res['access_token'],
            'openid' => $res['openid'],
            'lang' => 'zh_CN',
        ];
        $userinfo = $guzzleHelper->request($method, self::URI_AUTH_USERINFO, $query);
        $userinfo = $guzzleHelper->getResponse($userinfo);
        if (isset($userinfo['errcode'])) {
            (new WeixinErrorLog())->log($this->appid, $method, self::URI_AUTH_USERINFO, $query, $userinfo);
            throw new CustomNormal('微信授权失败');
        }
        return $userinfo;
    }
}
