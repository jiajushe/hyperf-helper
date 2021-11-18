<?php

namespace Jiajushe\HyperfHelper\MongoDB;

class WeixinErrorLog extends Model
{
    public function log(string $appid, $method, string $uri, array $query, array $response)
    {
        $this->create([
            'appid' => $appid,
            'method' => $method,
            'uri' => $uri,
            'query' => $query,
            'response' => $response,
        ]);
    }
}