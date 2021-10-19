<?php
/**
 * mongodb config
 */
return [
    'default' => [
        'host' => env('MONGODB_HOST', 'localhost'),
        'port' => env('MONGODB_PORT', 27017),
        'username' => env('MONGODB_USERNAME', ''),
        'password' => env('MONGODB_PASSWORD', ''),
        'database' => env('MONGODB_DATABASE', 'hyperf'),
    ],
];