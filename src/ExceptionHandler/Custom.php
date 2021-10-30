<?php

namespace Jiajushe\HyperfHelper\ExceptionHandler;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Jiajushe\HyperfHelper\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * 全局异常处理类
 * @author yun 2021-10-18 23:44:38
 */
class Custom extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $res = (new ResponseHelper())->error($throwable);
        if (method_exists($throwable, 'getHttpCode')) {
            $http_code = $throwable->getHttpCode();
        } else {
            $http_code = config('res_code.http.system_error');
        }
        $this->stopPropagation();
        return $response->withHeader(config('res_code.header_name'), config('res_code.header_value'))
            ->withStatus($http_code)
            ->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream($res));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}