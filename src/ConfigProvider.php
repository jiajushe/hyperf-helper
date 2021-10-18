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
                    'description' => 'The config of helper_function.',
                    'source' => __DIR__ . '/../publish/helper_function.php',
                    'destination' => BASE_PATH . '/config/autoload/helper_function.php',
                ],
            ],
        ];
    }
}