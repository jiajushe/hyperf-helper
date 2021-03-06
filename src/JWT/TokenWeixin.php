<?php

namespace Jiajushe\HyperfHelper\JWT;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use Jiajushe\HyperfHelper\Exception\CustomError;
use Jiajushe\HyperfHelper\Exception\CustomNormal;
use Throwable;
use UnexpectedValueException;

class TokenWeixin
{
    /**
     * 生成token
     * @param string $appid
     * @param string $sub
     * @param string $weixin
     * @return string
     * @throws CustomError
     */
    public function make(string $appid, string $sub, string $weixin): string
    {
        try {
            $config = config('jwt');
            $time = time();
            $payload = [
                'iss' => $appid,    //appid
                'sub' => $sub, //用户id
                'exp' => $time + $config['expire_second'], //过期时间
                'refresh' => $config['refresh_second'], //刷新时间
                'nbf' => $time, //某个时间点后才能访问
                'iat' => $time, //签发时间
                'weixin' => $weixin,// 微信类型
            ];
            return JWT::encode($payload, $config['secret']);
        } catch (Throwable $t) {
            throw new CustomError($t->getMessage());
        }
    }

    /**
     * 校验token
     * @param string $token
     * @param string $weixin
     * @return array
     * @throws CustomError
     * @throws CustomNormal
     */
    public function verify(string $token, string $weixin): array
    {
        try {
            $config = config('jwt');
            JWT::$leeway = $config['leeway_second'];
            $payload = JWT::decode($token, $config['secret'], ['HS256']);
            if (!isset($payload->weixin) || $weixin != $payload->weixin) {
                throw new SignatureInvalidException();
            }
            return (array)$payload;
        } catch (InvalidArgumentException $e) {
            throw new CustomNormal('没有签名', config('res_code.token'));
        } catch (BeforeValidException $e) {
            throw new CustomNormal('签名未生效', config('res_code.token'));
        } catch (ExpiredException $e) {
            throw new CustomNormal('签名已过期', config('res_code.token'));
        } catch (SignatureInvalidException | UnexpectedValueException $e) {
            throw new CustomNormal('签名无效', config('res_code.token'));
        } catch (Throwable $t) {
            throw new CustomError($t->getMessage());
        }
    }

    /**
     * 刷新token
     * @param array $payload
     * @return string|null
     * @throws CustomError
     */
    public function refresh(array $payload): ?string
    {
        if ($payload['refresh'] < ($payload['exp'] - time())) {
            return null;
        }
        return $this->make($payload['iss'], $payload['sub'], $payload['weixin']);
    }
}
