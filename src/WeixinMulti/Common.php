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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getRedis(): Redis
    {
        $container = ApplicationContext::getContainer();
        return $container->get(Redis::class);
    }

    /**
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

    public static function getRedisPrefix(): string
    {
        return Common::REDIS_PREFIX . 'ACCOUNT_TOKEN:';
    }

    /**
     * @param string $appid
     * @param string $secret
     * @return string
     * @throws CustomError
     */
    public static function getAccessToken(string $appid, string $secret): string
    {
        $redis_prefix = self::getRedisPrefix() . $appid;
        $redis = Common::getRedis();
        if ($access_token = $redis->get($redis_prefix)) {
            return $access_token;
        }
        $method = 'get';
        $query = [
            'appid' => $appid,
            'secret' => $secret,
            'grant_type' => 'client_credential',
        ];
        $guzzleHelper = new GuzzleHelper();
        $res = $guzzleHelper->getResponse($guzzleHelper->request($method, self::URI_ACCESS_TOKEN, $query));
        if (isset($res['errcode'])) {
            (new WeixinErrorLog())->log($appid, $method, self::URI_ACCESS_TOKEN, $query, $res);
            throw new CustomError('获取access_token失败');
        }
        $redis->set($redis_prefix, $res['access_token'], ($res['expires_in'] - 600));
        return $res['access_token'];
    }
}
