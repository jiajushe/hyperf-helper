<?php

namespace Jiajushe\HyperfHelper;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'commands' => [
            ],
            'listeners' => [],
            // 合并到  config/autoload/annotations.php 文件
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'res_code',
                    'description' => 'The config of response code.',
                    'source' => __DIR__ . '/../publish/res_code.php',
                    'destination' => BASE_PATH . '/config/autoload/res_code.php',
                ],
                [
                    'id' => 'helper_function',
                    'description' => 'The config of response code.',
                    'source' => __DIR__ . '/../publish/helper_function.php',
                    'destination' => BASE_PATH . '/config/autoload/helper_function.php',
                ],
            ],
        ];
    }
}