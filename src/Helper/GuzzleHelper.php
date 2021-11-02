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
namespace Jiajushe\HyperfHelper\Helper;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Jiajushe\HyperfHelper\Exception\CustomError;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GuzzleHelper
{
    /**
     * @Inject
     */
    protected ClientFactory $clientFactory;

    /**
     * @param string $method 请求方法
     * @param string $uri 请求地址
     * @param array $query 请求参数
     * @param array $options 请求选项
     * @return ResponseInterface
     * @throws CustomError
     */
    public function request(string $method, string $uri, array $query, array $options = []): ResponseInterface
    {
        $options['query'] = $query;
        try {
            $response = $this->clientFactory->create()->request($method, $uri, $options);
            if ($response->getStatusCode() != 200) {
                throw new CustomError('weixin connect error');
            }
            return $response;
        } catch (Throwable $t) {
            $this->errorLog($t, [$method, $uri, $options]);
            throw new CustomError('weixin connect error');
        }
    }

    public function toArray(ResponseInterface $response)
    {

    }

    /**
     * 错误记录.
     * @param Throwable $throwable
     * @param array $data 请求数据
     */
    private function errorLog(Throwable $throwable, array $data)
    {
//        todo http请求错误记录
        pp(
            $data,
            $throwable->getMessage(),
            $throwable->getCode(),
            $throwable->getLine(),
            $throwable->getFile(),
            $throwable->getTrace()
        );
    }
}
