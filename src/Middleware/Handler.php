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
     * @param array $res
     * @param ServerRequestInterface $request
     * @return mixed
     * @throws CustomError
     * @throws CustomNormal
     */
    public function getRequest(array $res, ServerRequestInterface $request): ServerRequestInterface
    {
        switch ($res['code']) {
            case config('res_code.normal'):
                $payload = $res['response']['payload'];
                $request = Context::override(ServerRequestInterface::class, function () use ($request, $payload) {
                    foreach ($payload as $index => $item) {
                        $request = $request->withAddedHeader('payload-' . $index, $item);
                    }
                    return $request;
                });
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
     * @param array $res
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getResponse(array $res, ResponseInterface $response): ResponseInterface
    {
        //刷新token
        if ($res['response']['new_token']) {
            $response = $response->withHeader('Authorization', $res['response']['new_token']);
        }
        return $response;
    }
}