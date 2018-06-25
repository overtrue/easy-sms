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
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
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

    const ENDPOINT_URL_TEMPLATE = 'https://%s.253.com/msg/send/json';

    const CHANNEL_VALIDATE_CODE  = 'smsbj1';
    const CHANNEL_PROMOTION_CODE = 'smssh1';

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
            'msg' => $this->wrapChannelContent($message->getContent($this), $config),
        ];

        $result = $this->post($this->getEndpointUrl($config), $params);

        $formatResult = $this->formatResult($result);

        if (!empty($formatResult[1])) {
            throw new GatewayErrorException($result, $formatResult[1], $formatResult);
        }

        return $result;
    }

    /**
     * Gets the endpoint url.
     *
     * @param      \Overtrue\EasySms\Support\Config  $config  The configuration
     *
     * @return     string                            The endpoint url.
     */
    protected function getEndpointUrl(Config $config)
    {
        $channel = $this->getChannel($config);
        return sprintf(self::ENDPOINT_URL_TEMPLATE, $channel);
    }

    /**
     * Gets the channel.
     *
     * @throws     \Overtrue\EasySms\Exceptions\InvalidArgumentException  (description)
     *
     * @return     string                                                 The channel.
     */
    protected function getChannel(Config $config)
    {
        $channel = $config->get('channel', self::CHANNEL_VALIDATE_CODE);
        if (in_array($channel, [self::CHANNEL_VALIDATE_CODE, self::CHANNEL_PROMOTION_CODE])) {
            throw new InvalidArgumentException('Invalid channel for ChuanglanGateway.');
        }

        return $channel;
    }

    /**
     * wrap channel content
     *
     * @param      string                                                 $content  The content
     * @param      \Overtrue\EasySms\Support\Config                       $config   The configuration
     *
     * @throws     \Overtrue\EasySms\Exceptions\InvalidArgumentException
     *
     * @return     string
     */
    protected function wrapChannelContent(string $content, Config $config)
    {
        $channel = $this->getChannel($config);

        if ($channel == self::CHANNEL_PROMOTION_CODE) {
            $sign = (string) $config->get('sign', '');
            if (empty($sign)) {
                throw new InvalidArgumentException('Invalid sign for ChuanglanGateway when using promotion channel');
            }

            $unsubscribe = (string) $config->get('unsubscribe', '');
            if (empty($unsubscribe)) {
                throw new InvalidArgumentException('Invalid unsubscribe for ChuanglanGateway when using promotion channel');
            }

            $content = $sign . $content . $unsubscribe;
        }

        return $content;
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
