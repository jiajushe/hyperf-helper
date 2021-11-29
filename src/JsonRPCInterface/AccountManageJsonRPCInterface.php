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

interface AccountManageJsonRPCInterface
{
    /**
     * 通过customer_id获取信息.
     */
    public function getCustomerInfo(string $master_id): array;
}
