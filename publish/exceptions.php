<?php

declare(strict_types=1);

return [
    'handler' => [
        'http' => [
            Jiajushe\HyperfHelper\ExceptionHandler\MongoDB::class,
            Jiajushe\HyperfHelper\ExceptionHandler\Custom::class,
        ],
    ],
];
