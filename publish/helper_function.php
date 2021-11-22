<?php
/**
 * 公用函数
 */

/**
 * 自定义打印方法
 * @param ...$arr
 * @author yun 2021-10-18 14:33:31
 */
function pp(...$arr)
{
    foreach ($arr as $item) {
        if (is_array($item)) {
            print_r($item);
        } else {
            var_dump($item);
        }
        print_r(PHP_EOL);
    }
}

/**
 * 从请求头获取主id
 */
function getMid(Hyperf\HttpServer\Contract\RequestInterface $request): string
{
    $id = $request->getHeaderLine('payload-sub');
    $pid = $request->getHeaderLine('payload-pid');
//    return $pid == $master_pid ? $id : $pid;
    return !$pid ? $id : $pid;
}