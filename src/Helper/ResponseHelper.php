<?php

namespace Jiajushe\HyperfHelper\Helper;

use Hyperf\Utils\Codec\Json;
use Jiajushe\HyperfHelper\Exception\CustomNormal;
use Throwable;

/**
 * api返回信息处理类
 * @author yun 2021-10-18 23:46:30
 */
class ResponseHelper
{
    /**
     * 正常接口返回数据格式
     * @param mixed $response
     * @param string $msg
     * @param int|null $code
     * @return array
     * @author yun 2021-10-12 11:24:17
     */
    public function normal($response, string $msg = 'success', int $code = null): array
    {
        return [
            'code' => $code === null ? config('res_code.normal') : $code,
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
            'msg' => $throwable->getMessage(),
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
            'msg' => $throwable->getMessage(),
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
    public function isDev(): bool
    {
        $app_env = config('app_env');
        return $app_env === 'dev';
    }

    /**
     * 返回错误数据格式
     * @param Throwable $throwable
     * @return string
     * @author yun 2021-10-12 14:02:53
     */
    public function error(Throwable $throwable): string
    {
        if ($this->isDev() && !($throwable instanceof CustomNormal)) {
            $res = $this->devErrorJson($throwable);
        } else {
            $res = $this->errorJson($throwable);
        }
        return $res;
    }

    /**
     * 表单验证错误信息返回格式
     * @param string $msg
     * @return string
     * @author yun 2021-10-26 10:11:05
     */
    public function validation(string $msg): string
    {
        return Json::encode([
            'code' => config('res_code.alert'),
            'msg' => $msg,
        ]);
    }
}