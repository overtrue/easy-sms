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

use function GuzzleHttp\json_encode;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class ChuanglanGateway.
 *
 * @see https://www.253.com/api-docs-1.html
 */
class ChuanglanGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'https://sms.253.com/msg/send';

    /**
     * @param array|int|string                             $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface $message
     * @param \Overtrue\EasySms\Support\Config             $config
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\GatewayErrorException;
     */
    public function send($to, MessageInterface $message, Config $config)
    {
        $params = [
            'un' => $config->get('un'),
            'pw' => $config->get('pw'),
            'phone' => $to,
            'msg' => $message->getContent(),
        ];

        $result = $this->get(self::ENDPOINT_URL, $params);

        $formatResult = $this->formatResult($result);

        if ($formatResult[1]) {
            throw new GatewayErrorException($formatResult[3], $formatResult[1], $result);
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