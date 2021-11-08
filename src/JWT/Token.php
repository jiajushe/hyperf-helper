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
use stdClass;
use UnexpectedValueException;

class Token
{
    /**
     * 生成token
     * @param string $model
     * @param stdClass $user
     * @return string
     * @throws CustomError
     */
    public function make(string $model, stdClass $user): string
    {
        try {
            $config = config('jwt');
            $time = time();
            $sub = $user->id;
            $payload = [
                'iss' => $model,    //签发者
                'sub' => $sub, //用户ID
                'exp' => $time + $config['expire_second'], //过期时间
                'refresh' => $config['refresh_second'], //刷新时间
                'nbf' => $time, //某个时间点后才能访问
                'iat' => $time, //签发时间
            ];
            if (isset($user->pid)) {
                $payload['pid'] = $user->pid;
            }
//            if (isset($user->grade_name)) {
//                $payload['grade-name'] = $user->grade_name;
//            }
            return JWT::encode($payload, $config['secret']);
        } catch (Throwable $t) {
            throw new CustomError($t->getMessage());
        }
    }

    /**
     * 校验token
     * @param string $model
     * @param string $user_token
     * @return object
     * @throws CustomNormal|CustomError
     */
    public function verify(string $model, string $user_token): object
    {
        try {
            $config = config('jwt');
            JWT::$leeway = $config['leeway_second'];
            $payload = JWT::decode($user_token, $config['secret'], ['HS256']);
            if ($payload->iss != $model) {
                throw new SignatureInvalidException();
            }
            return $payload;
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
     * @param stdClass $payload
     * @return string|null
     * @throws CustomError
     */
    public function refresh(stdClass $payload): ?string
    {
        if ($payload->refresh < ($payload->exp - time())) {
            return null;
        }
        $user_id = $payload->sub;
        $model = new $payload->iss ();
        $user = $model->find($user_id);
        return $this->make($payload->iss, $user);
    }
}
