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
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class DreamNetGateway.
 *
 * @see http://ip:port/MWGate/wmgw.asmx/MongateSendSubmit
 */
class DreamNetGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'http://%s:%s/MWGate/wmgw.asmx/MongateSendSubmit';

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
        $endpoint = $this->buildEndpoint(
            $config->get('ip'),
            $config->get('port')
        );

        $arr = explode(',', $to);
        
        $result = $this->post($endpoint, [
            'userId'     => $config->get('user_id'),
            'password'   => $config->get('password'),
            'pszSubPort' => $config->get('psz_sub_port'),
            'pszMobis'   => $to,
            'pszMsg'     => $message->getContent(),
            'iMobiCount' => count($arr),
            'MsgId'      => 0,
        ]);

        // $result = simplexml_load_string($result);

        if (! $result) {
            throw new GatewayErrorException('xml解析失败', 500);
        }

        // $result = $result[0];
        if (strlen($result) < 16) {
            throw new GatewayErrorException('发送失败', 500);
        }

        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @param string $ip
     * @param string $port
     *
     * @return string
     */
    protected function buildEndpoint($ip, $port)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $ip, $port);
    }
}
