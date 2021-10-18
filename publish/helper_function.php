<?php
/**
 * 自定义公用函数
 */

/**
 * 自定义打印方法
 * @param ...$arr
 * @author yun 2021-10-18 14:33:31
 */
function pp(...$arr)
{
    foreach ($arr as $item) {
        print_r($item);
        print_r(PHP_EOL);
    }
}