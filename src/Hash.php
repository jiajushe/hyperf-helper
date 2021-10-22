<?php

namespace Jiajushe\HyperfHelper;


use Jiajushe\HyperfHelper\Exception\CustomNormal;

class Hash
{
    /**
     * 生成hash密码
     * @param string $password
     * @param string $algo
     * @param array|int[] $option
     * @return false|string|null
     * @throws CustomNormal
     * @author yun 2021-10-12 10:18:35
     */
    public function make(string $password, string $algo = PASSWORD_DEFAULT, array $option = ['cost' => 10])
    {
        if (!$hash = password_hash($password, $algo,$option)) {
            throw new CustomNormal('make hash error', 5);
        }
        return $hash;
    }

    /**
     * 验证hash密码
     * @param string $password
     * @param string $hash
     * @return bool
     * @author yun 2021-10-12 10:18:40
     */
    public function verify(string $password, string $hash):bool
    {
        return password_verify($password, $hash);
    }
}