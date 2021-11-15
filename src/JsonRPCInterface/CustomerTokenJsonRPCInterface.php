<?php

namespace Jiajushe\HyperfHelper\JsonRPCInterface;

interface CustomerTokenJsonRPCInterface
{
    /**
     * customer token 验证.
     * @param string $token
     */
    public function verify(string $token): array;

    /**
     * customer permission 权限验证.
     * @param string $token
     * @param string $method
     * @param string $path
     */
    public function permission(string $token, string $method, string $path): array;
}