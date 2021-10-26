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
//                volumes
                [
                    'id' => 'volumes',
                    'description' => 'The volumes.',
                    'source' => __DIR__ . '/../publish/volumes',
                    'destination' => BASE_PATH . '/volumes',
                ],
//                .gitignore
                [
                    'id' => 'gitignore',
                    'description' => 'The .gitignore.',
                    'source' => __DIR__ . '/../publish/.gitignore',
                    'destination' => BASE_PATH . '/.gitignore',
                ],
//                dockerfile
                [
                    'id' => 'dockerfile',
                    'description' => 'The dockerfile.',
                    'source' => __DIR__ . '/../publish/docker/Dockerfile',
                    'destination' => BASE_PATH . '/Dockerfile',
                ],
//                docker-compose
                [
                    'id' => 'docker-compose',
                    'description' => 'The docker-compose.',
                    'source' => __DIR__ . '/../publish/docker/docker-compose.yml',
                    'destination' => BASE_PATH . '/docker-compose.yml',
                ],
//                README.md
                [
                    'id' => 'readme',
                    'description' => 'The README.',
                    'source' => __DIR__ . '/../publish/README.md',
                    'destination' => BASE_PATH . '/README.md',
                ],
//                php-cs-fixer
                [
                    'id' => 'readme',
                    'description' => 'The README.',
                    'source' => __DIR__ . '/../publish/.php-cs-fixer.php',
                    'destination' => BASE_PATH . '/.php-cs-fixer.php',
                ],
            ],
        ];
    }
}