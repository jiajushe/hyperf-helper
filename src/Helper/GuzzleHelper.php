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
use Hyperf\Utils\Codec\Json;
use Jiajushe\HyperfHelper\Exception\CustomError;
use Jiajushe\HyperfHelper\MongoDB\GuzzleErrorLog;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GuzzleHelper
{
    /**
     * @Inject
     */
    protected ClientFactory $clientFactory;

    /**
     * 发送请求
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
                throw new CustomError('guzzle connect error');
            }
            return $response;
        } catch (Throwable $t) {
            $this->errorLog($t, ['method' => $method, 'uri' => $uri, 'query' => $query, 'options' => $options]);
            throw new CustomError('guzzle connect error');
        }
    }

    /**
     * 获取返回信息
     * @param ResponseInterface $response
     * @return mixed
     */
    public function getResponse(ResponseInterface $response)
    {
        return Json::decode($response->getBody()->getContents());
    }

    /**
     * 错误记录.
     * @param Throwable $throwable
     * @param array $data 请求数据
     */
    private function errorLog(Throwable $throwable, array $data)
    {
        (new GuzzleErrorLog())->create([
            'method' => $data['method'],
            'uri' => $data['uri'],
            'query' => $data['query'],
            'options' => $data['options'],
            'code' => $throwable->getCode(),
            'msg' => $throwable->getMessage(),
            'class_name' => get_class($throwable),
            'line' => $throwable->getLine(),
            'file' => $throwable->getFile(),
            'previous' => $throwable->getPrevious(),
            'trace' => $throwable->getTrace()
        ]);
    }
}
