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
 * Class ChuanglanGateway.
 *
 * @see https://zz.253.com/v5.html#/api_doc
 */
class ChuanglanGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'https://sms.253.com/msg/send/json';

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
        $params = [
            'username' => $config->get('username'),
            'password' => $config->get('password'),
            'phone' => $to->getNumber(),
            'msg' => $message->getContent($this),
        ];

        $result = $this->get(self::ENDPOINT_URL, $params);

        $formatResult = $this->formatResult($result);

        if (!empty($formatResult[1])) {
            throw new GatewayErrorException($result, $formatResult[1], $formatResult);
        }

        return $result;
    }

    /**
     * @param $result  http return from 253 service
     *
     * @return array
     */
    protected function formatResult($result)
    {
        $result = str_replace("\n", ',', $result);

        return explode(',', $result);
    }
}
