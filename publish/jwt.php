<?php
/**
 * jwt config
 */
return [
    'secret' => env('JWT_SECRET', '3dfa538edae7c1021a234afe9c7906c3'),
    'expire_second' => env('JWT_EXPIRE_SECOND', 72000),
    'refresh_second' => env('JWT_REFRESH_SECOND', 1200),
    'leeway_second' => 60,
];