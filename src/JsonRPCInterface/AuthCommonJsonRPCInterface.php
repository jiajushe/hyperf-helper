<?php

declare(strict_types=1);
/**
 * This file is part of Jiajushe.
 *
 * @link
 * @document
 * @contact
 * @license
 */
namespace Jiajushe\HyperfHelper\JsonRPCInterface;

interface AuthCommonJsonRPCInterface
{
    /**
     * 通过customer_id获取信息.
     * @param string $customer_id
     */
    public function getInfoByCustomerId(string $customer_id);
}
