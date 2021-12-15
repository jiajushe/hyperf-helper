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

use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Jiajushe\HyperfHelper\Exception\CustomError;
use Jiajushe\HyperfHelper\Helper\GuzzleHelper;
use Jiajushe\HyperfHelper\MongoDB\WeixinErrorLog;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Common
{
    public const URI_ACCESS_TOKEN = 'https://api.weixin.qq.com/cgi-bin/token';//获取access_token

    public const REDIS_PREFIX = 'WEIXIN:';

    /**
     * 获取Redis对象
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getRedis(): Redis
    {
        $container = ApplicationContext::getContainer();
        return $container->get(Redis::class);
    }

    /**
     * 创建 nonce str
     * @param int $length
     * @return string
     */
    public static function createNonceStr(int $length = 16): string
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取 access token
     * @param string $appid
     * @param string $secret
     * @param string $weixin_type
     * @return string
     * @throws CustomError
     */
    public static function getAccessToken(string $appid, string $secret, string $weixin_type): string
    {
        $weixin_type = strtoupper($weixin_type) . ':';
        $redis_key = self::REDIS_PREFIX . 'ACCOUNT_TOKEN:' . $weixin_type . $appid;
        $redis = Common::getRedis();
        if ($access_token = $redis->get($redis_key)) {
            return $access_token;
        }
        $method = 'get';
        $query = [
            'appid' => $appid,
            'secret' => $secret,
            'grant_type' => 'client_credential',
        ];
        $res = self::request($weixin_type, $appid, $method, self::URI_ACCESS_TOKEN, $query);
        $redis->set($redis_key, $res['access_token'], ($res['expires_in'] - 600));
        return $res['access_token'];
    }

    /**
     * @param string $weixin_type
     * @param string $appid
     * @param string $method
     * @param string $uri
     * @param array $query
     * @param array $options
     * @return mixed
     * @throws CustomError
     */
    public static function request(string $weixin_type, string $appid, string $method, string $uri, array $query, array $options = [])
    {
        $guzzleHelper = new GuzzleHelper();
        $res = $guzzleHelper->getResponse($guzzleHelper->request($method, $uri, $query, $options));
        $error = false;
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            if ($uri == Miniprogram::URI_SEND_SUBSCRIBE_MESSAGE && $res['errcode'] == 43101) {
                $error = false;
            } else {
                $error = true;
            }
        }
        if ($error) {
            (new WeixinErrorLog())->log($weixin_type, $appid, $method, self::URI_ACCESS_TOKEN, $query, $res);
            throw new CustomError('weixin api error');
        }
        return $res;
    }
}
