<?php

namespace Jiajushe\HyperfHelper\MongoDB;

class WeixinErrorLog extends Model
{
    public function log(string $weixin_type,string $appid, $method, string $uri, array $query, array $response)
    {
        $this->create([
            'weixin_type' => $weixin_type,
            'appid' => $appid,
            'method' => $method,
            'uri' => $uri,
            'query' => $query,
            'response' => $response,
        ]);
    }
}