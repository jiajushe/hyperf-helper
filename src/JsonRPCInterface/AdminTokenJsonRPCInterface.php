<?php
namespace Jiajushe\HyperfHelper\JsonRPCInterface;

interface AdminTokenJsonRPCInterface
{
    public function verify(string $token);
}