<?php
/**
 * 返回code设置
 * @author yun 2021-10-11 17:31:28
 */
return [
    'header_name' => 'Hyperf',  //返回头名称
    'header_value' => env('APP_NAME', 'hyperf'),    //返回头值
    'http' => [ //http状态码
        'normal' => 200,    //正常返回
        'system_error' => 500   //系统错误
    ],
    'normal' => 0,  //正常返回
    'alert' => 1,   //提示信息
    'token' => 2,   //token
    'error' => 5,   //系统错误
];