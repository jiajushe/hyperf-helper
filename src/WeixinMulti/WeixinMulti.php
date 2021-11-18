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
namespace Jiajushe\HyperfHelper\WeixinMulti;

use stdClass;

class WeixinMulti
{

    protected stdClass $config;

    public function __construct(string $master_id, string $model)
    {
        $weixinConfig = new $model();
        $this->config = $weixinConfig->where('master_id', '=', $master_id)->find();
    }

    /**
     * 返回微信公众号操作对象.
     * @return Offiaccount
     */
    public function offiaccount(): Offiaccount
    {
        return new Offiaccount($this->config->offiaccount_appid, $this->config->offiaccount_secret);
    }


}
