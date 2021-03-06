<?php

namespace Jiajushe\HyperfHelper\ExceptionHandler;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Codec\Json;
use Jiajushe\HyperfHelper\Helper\ResponseHelper;
use Jiajushe\HyperfHelper\MongoDB\SystemErrorLog;
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
        $system_error = config('res_code.http.system_error');
        if (method_exists($throwable, 'getHttpCode')) {
            $http_code = $throwable->getHttpCode();
        } else {
            $http_code = $system_error;
        }
        if ($http_code == $system_error) {
            (new SystemErrorLog())->create([
                'code' => $throwable->getCode(),
                'msg' => $throwable->getMessage(),
                'class_name' => get_class($throwable),
                'line' => $throwable->getLine(),
                'file' => $throwable->getFile(),
                'previous' => $throwable->getPrevious(),
                'trace' => $throwable->getTrace()
            ]);
        }
        $this->stopPropagation();
        return $response->withHeader(config('res_code.header_name'), config('res_code.header_value'))
            ->withStatus($http_code)
            ->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream(Json::encode($res)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}