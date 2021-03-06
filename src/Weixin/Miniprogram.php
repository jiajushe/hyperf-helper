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

namespace Jiajushe\HyperfHelper\Weixin;

use Hyperf\Utils\Str;
use Jiajushe\HyperfHelper\Exception\CustomError;

class Miniprogram
{
    public const URI_CODE_TO_SESSION = 'https://api.weixin.qq.com/sns/jscode2session';//code2Session
    public const URI_SEND_SUBSCRIBE_MESSAGE = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send';//发订阅消息

    protected string $appid;

    protected string $secret;

    protected const WEIXIN_TYPE = 'MINIPROGRAM';

    public function __construct(string $appid, string $secret)
    {
        $this->appid = $appid;
        $this->secret = $secret;
    }

    public function getRedisPrefix(): string
    {
        return Common::REDIS_PREFIX . 'MINIPROGRAM:';
    }

    /**
     * @param string $encrypted_data
     * @param string $iv
     * @param string $session_key
     * @return array
     * @throws CustomError
     */
    public function decryptData(string $encrypted_data, string $iv, string $session_key): array
    {
        $aesKey = base64_decode($session_key);
        if (strlen($iv) != 24) {
            throw new CustomError('非法iv');
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encrypted_data);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        if (!$result) {
            throw new CustomError('aes 解密失败:1');
        }
        $dataObj = json_decode($result);
        if ($dataObj == NULL) {
            throw new CustomError('aes 解密失败:2');
        }
        if ($dataObj->watermark->appid != $this->appid) {
            throw new CustomError('aes 解密失败:3');
        }
        return (array)$dataObj;
    }

    /**
     * @param string $js_code
     * @return array
     * @throws CustomError
     */
    public function code2Session(string $js_code): array
    {
        $method = 'get';
        $query = [
            'appid' => $this->appid,
            'secret' => $this->secret,
            'js_code' => $js_code,
            'grant_type' => 'authorization_code',
        ];
        return Common::request(self::WEIXIN_TYPE, $this->appid, $method, self::URI_CODE_TO_SESSION, $query);
    }

    /**
     * 发订阅消息
     * @param array $data
     * [
     * 'touser' => 'oM5LW5d0Tf12or34QxrRbO7gQBjw',
     * 'template_id' => 'scbDq9zNS5NXmSa3r7fVTIBn-8XVvX6O9TDBk4HxQpM',
     * 'page' => '/pages/index/index',
     * 'data' => [
     * 'thing2' => [
     * 'value' => '测试',
     * ],
     * 'thing3' => [
     * 'value' => '18666380320',
     * ],
     * ],
     * ]
     * @return bool
     * @throws CustomError
     */
    public function subscribeMessage(array $data)
    {
        $method = 'post';
        $query = [
            'access_token' => Common::getAccessToken($this->appid, $this->secret, self::WEIXIN_TYPE),
        ];
        foreach ($data['data'] as $index => $datum) {
            if (Str::contains($index, 'thing')) {
                $data['data'][$index]['value'] = Str::substr($datum['value'], 0, 20);
            }
        }
        $options = [
            'json' => $data,
        ];
        Common::request(self::WEIXIN_TYPE, $this->appid, $method, self::URI_SEND_SUBSCRIBE_MESSAGE, $query, $options);
        return true;
    }
}
