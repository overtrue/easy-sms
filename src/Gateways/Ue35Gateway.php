<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class Ue35Gateway.
 *
 * @see https://shimo.im/docs/380b42d8cba24521
 */
class Ue35Gateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_HOST = 'sms.ue35.cn';

    const ENDPOINT_URI = '/sms/interface/sendmess.htm';

    const SUCCESS_CODE = 1;

    /**
     * Send message.
     *
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface     $message
     * @param \Overtrue\EasySms\Support\Config                 $config
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\GatewayErrorException ;
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $params = [
            'username' => $config->get('username'),
            'userpwd' => $config->get('userpwd'),
            'mobiles' => $to->getNumber(),
            'content' => $message->getContent($this),
        ];

        $headers = [
            'host' => static::ENDPOINT_HOST,
            'content-type' => 'application/json',
            'user-agent' => 'PHP EasySms Client',
        ];

        $result = $this->request('get', self::getEndpointUri().'?'.http_build_query($params), ['headers' => $headers]);
        if (is_string($result)) {
            $result = json_decode(json_encode(simplexml_load_string($result)), true);
        }

        if (self::SUCCESS_CODE != $result['errorcode']) {
            throw new GatewayErrorException($result['message'], $result['errorcode'], $result);
        }

        return $result;
    }

    public static function getEndpointUri()
    {
        return 'http://'.static::ENDPOINT_HOST.static::ENDPOINT_URI;
    }
}
