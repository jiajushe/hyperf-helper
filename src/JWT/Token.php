<?php

namespace Jiajushe\HyperfHelper\JWT;

use Firebase\JWT\JWT;

class Token
{
    public function getToken(string $model,array $user)
    {
        $config = config('jwt');
        pp($config);
        $payload = [

        ];
//        JWT::encode()
    }
}
