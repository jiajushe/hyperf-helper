<?php
namespace Jiajushe\HyperfHelper\JsonRPCInterface;

interface CustomerTokenJsonRPCInterface
{
    public function verify(string $token);
}