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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Common
{
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
}
