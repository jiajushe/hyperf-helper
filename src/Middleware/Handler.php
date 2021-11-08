<?php

namespace Jiajushe\HyperfHelper\Middleware;

use Hyperf\Utils\Context;
use Jiajushe\HyperfHelper\Exception\CustomError;
use Jiajushe\HyperfHelper\Exception\CustomNormal;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Handler
{
    /**
     * 检查 token rpc 的返回信息
     * @param array $res
     * @param ServerRequestInterface $request
     * @return mixed
     * @throws CustomError
     * @throws CustomNormal
     */
    public function checkTokenJsonRPC(array $res, ServerRequestInterface $request): ServerRequestInterface
    {
        switch ($res['code']) {
            case config('res_code.normal'):
                $request = $this->payloadInHeader($request, $res['response']['payload']);
                break;
            case config('res_code.alert'):
            case config('res_code.token'):
                throw new CustomNormal($res['msg'], $res['code']);
            default:
                throw new CustomError($res['msg'], $res['code']);
        }
        return $request;
    }

    /**
     * 添加 payload 到请求头
     * @param ServerRequestInterface $request
     * @param array $payload
     * @return ServerRequestInterface
     */
    public function payloadInHeader(ServerRequestInterface $request, array $payload): ServerRequestInterface
    {
        return Context::override(ServerRequestInterface::class, function () use ($request, $payload) {
            foreach ($payload as $index => $item) {
                $request = $request->withAddedHeader('payload-' . $index, $item);
            }
            return $request;
        });
    }

    /**
     * 添加 new token 到返回头
     * @param $token
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function newTokenInHeader($token, ResponseInterface $response): ResponseInterface
    {
        if ($token) {
            $response = $response->withHeader('Authorization', $token);
        }
        return $response;
    }
}