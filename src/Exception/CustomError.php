<?php

namespace Jiajushe\HyperfHelper\Exception;

use Exception;
use Throwable;

/**
 * 系统异常类
 * @author yun 2021-10-18 23:42:54
 */
class CustomError extends Exception
{
    /**
     * @param string $message
     * @param int|null $code
     * @param Throwable|null $previous
     * @author yun 2021-10-12 10:48:37
     */
    public function __construct($message = "", int $code = null, Throwable $previous = null)
    {
        if ($code === null) {
            $code = config('res_code.error');
        }
        parent::__construct($message, $code, $previous);
    }

    public function getHttpCode()
    {
        return config('res_code.http.system_error');
    }
}