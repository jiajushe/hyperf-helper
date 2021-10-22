<?php

namespace Jiajushe\HyperfHelper;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
//                返回状态码配置文件
                [
                    'id' => 'res_code',
                    'description' => 'The config of res_code.',
                    'source' => __DIR__ . '/../publish/res_code.php',
                    'destination' => BASE_PATH . '/config/autoload/res_code.php',
                ],
//                公用函数文件
                [
                    'id' => 'helper_function',
                    'description' => 'The function of common.',
                    'source' => __DIR__ . '/../publish/helper_function.php',
                    'destination' => BASE_PATH . '/config/autoload/helper_function.php',
                ],
//                mongodb配置文件
                [
                    'id' => 'mongodb',
                    'description' => 'The config of mongodb.',
                    'source' => __DIR__ . '/../publish/mongodb.php',
                    'destination' => BASE_PATH . '/config/autoload/mongodb.php',
                ],
//                jwt配置文件
                [
                    'id' => 'jwt',
                    'description' => 'The config of jwt.',
                    'source' => __DIR__ . '/../publish/jwt.php',
                    'destination' => BASE_PATH . '/config/autoload/jwt.php',
                ],
//                异常处理配置文件
                [
                    'id' => 'exceptions',
                    'description' => 'The config of jwt.',
                    'source' => __DIR__ . '/../publish/exceptions.php',
                    'destination' => BASE_PATH . '/config/autoload/exceptions.php',
                ],
            ],
        ];
    }
}