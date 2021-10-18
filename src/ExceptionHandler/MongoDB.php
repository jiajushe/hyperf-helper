<?php

namespace Jiajushe\HyperfHelper\ExceptionHandler;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Str;
use Jiajushe\HyperfHelper\Response;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class MongoDB  extends ExceptionHandler
{

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
//        todo  错误记录
        $res = (new Response())->isDevRes($throwable);
        $this->stopPropagation();
        return $response->withHeader(config('res_code.header_name'), config('res_code.header_value'))
            ->withStatus(config('res_code.http.system_error'))
            ->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream($res));
    }

    public function isValid(Throwable $throwable): bool
    {
        return Str::contains(get_class($throwable), 'MongoDB\Driver\Exception');
    }
}
{

}