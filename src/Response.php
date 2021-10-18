<?php

namespace Jiajushe\HyperfHelper;

use Hyperf\Utils\Codec\Json;
use Throwable;

class Response
{
    /**
     * 正常接口返回数据格式
     * @param array $response
     * @param string $msg
     * @return array
     * @author yun 2021-10-12 11:24:17
     */
    public function normal(array $response, string $msg = 'success'): array
    {
        return [
            'code' => config('res_code.normal'),
            'msg' => $msg,
            'response' => $response,
        ];
    }

    /**
     * 系统错误返回格式
     * @param Throwable $throwable
     * @return string
     * @author yun 2021-10-11 17:27:52
     */
    public function errorJson(Throwable $throwable): string
    {
        $code = $throwable->getCode();
        if ($code == 0) {
            $code = config('res_code.error');
        }
        return Json::encode([
            'code' => $code,
            'error_msg' => $throwable->getMessage(),
        ]);
    }

    /**
     * 开发环境系统错误返回格式
     * @param Throwable $throwable
     * @return string
     * @author yun 2021-10-12 11:13:53
     */
    public function devErrorJson(Throwable $throwable): string
    {
        $code = $throwable->getCode();
        if ($code == 0) {
            $code = config('res_code.error');
        }
        return Json::encode([
            'code' => $code,
            'error_msg' => $throwable->getMessage(),
            'class_name' => get_class($throwable),
            'line' => $throwable->getLine(),
            'file' => $throwable->getFile(),
            'previous' => $throwable->getPrevious(),
            'trace' => $throwable->getTrace()
        ]);
    }

    /**
     * 判断是否dev环境
     * @return bool
     * @author yun 2021-10-12 14:02:57
     */
    public function isDev():bool
    {
        $app_env = config('app_env');
        return $app_env === 'dev';
    }

    /**
     * 判断是否dev环境返回错误格式
     * @param Throwable $throwable
     * @return string
     * @author yun 2021-10-12 14:02:53
     */
    public function isDevRes(Throwable $throwable): string
    {
        $app_env = config('app_env');
        if ($app_env === 'dev') {
            $res = $this->devErrorJson($throwable);
        } else {
            $res = $this->errorJson($throwable);
        }
        return $res;
    }
}