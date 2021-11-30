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

use Jiajushe\HyperfHelper\Exception\CustomError;
use stdClass;

class WeixinMulti
{

    protected stdClass $config;

    public function __construct(string $master_id, string $model)
    {
        $weixinConfig = new $model();
        $config = $weixinConfig->where('master_id', '=', $master_id)->find();
        if (!$config) {
            throw new CustomError('未配置微信');
        }
        $this->config = $config;
    }

    /**
     * 返回微信公众号操作对象.
     * @return Offiaccount
     */
    public function offiaccount(): Offiaccount
    {
        return new Offiaccount($this->config->offiaccount_appid, $this->config->offiaccount_secret);
    }

    /**
     * 返回微信小程序操作对象.
     * @return Miniprogram
     */
    public function miniprogram(): Miniprogram
    {
        return new Miniprogram($this->config->miniprogram_appid, $this->config->miniprogram_secret);
    }
}
