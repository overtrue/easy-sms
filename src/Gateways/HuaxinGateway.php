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
 * Class HuaxinGateway.
 *
 * @see http://www.ipyy.com/help/
 */
class HuaxinGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'http://%s/smsJson.aspx';

    /**
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
        $endpoint = $this->buildEndpoint($config->get('ip'));

        $result = $this->post($endpoint, [
            'userid' => $config->get('user_id'),
            'account' => $config->get('account'),
            'password' => $config->get('password'),
            'mobile' => $to->getNumber(),
            'content' => $message->getContent($this),
            'sendTime' => '',
            'action' => 'send',
            'extno' => $config->get('ext_no'),
        ]);

        if ('Success' !== $result['returnstatus']) {
            throw new GatewayErrorException($result['message'], 400, $result);
        }

        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @param string $ip
     *
     * @return string
     */
    protected function buildEndpoint($ip)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $ip);
    }
}
