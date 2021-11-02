<?php

namespace Jiajushe\HyperfHelper\ExceptionHandler;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Jiajushe\HyperfHelper\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * 表单验证异常处理类
 * @author yun 2021-10-26 10:15:31
 */
class Validation extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stopPropagation();
        /** @var ValidationException $throwable */
        $body = $throwable->validator->errors()->first();
        if (!$response->hasHeader('content-type')) {
            $response = $response->withAddedHeader('content-type', 'text/plain; charset=utf-8');
        }
        return $response->withHeader(config('res_code.header_name'), config('res_code.header_value'))
            ->withStatus(config('res_code.http.normal'))
            ->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream((new ResponseHelper())->validation($body)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}