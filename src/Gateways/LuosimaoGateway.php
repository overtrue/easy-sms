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
 * Class LuosimaoGateway.
 *
 * @see https://luosimao.com/docs/api/
 */
class LuosimaoGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'https://%s.luosimao.com/%s/%s.%s';

    const ENDPOINT_VERSION = 'v1';

    const ENDPOINT_FORMAT = 'json';

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
        $endpoint = $this->buildEndpoint('sms-api', 'send');

        $result = $this->post($endpoint, [
            'mobile' => $to->getNumber(),
            'message' => $message->getContent($this),
        ], [
            'Authorization' => 'Basic '.base64_encode('api:key-'.$config->get('api_key')),
        ]);

        if ($result['error']) {
            throw new GatewayErrorException($result['msg'], $result['error'], $result);
        }

        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @param string $type
     * @param string $function
     *
     * @return string
     */
    protected function buildEndpoint($type, $function)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $type, self::ENDPOINT_VERSION, $function, self::ENDPOINT_FORMAT);
    }
}
