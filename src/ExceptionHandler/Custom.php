<?php

namespace Jiajushe\HyperfHelper\ExceptionHandler;

//use Hyperf\Contract\ConfigInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;

//use Hyperf\Utils\ApplicationContext;
use Jiajushe\HyperfHelper\Response;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class Custom extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $res = (new Response())->isDevRes($throwable);
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