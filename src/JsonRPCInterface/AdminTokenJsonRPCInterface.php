<?php
namespace Jiajushe\HyperfHelper\JsonRPCInterface;

interface AdminTokenJsonRPCInterface
{
    /**
     * admin token 验证.
     * @param string $token
     * @return mixed
     */
    public function verify(string $token);

    /**
     * admin permission 权限验证.
     * @param string $token
     * @param string $method
     * @param string $path
     * @return mixed
     */
    public function permission(string $token, string $method, string $path);
}