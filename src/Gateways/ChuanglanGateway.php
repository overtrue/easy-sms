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
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
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

    /**
     * URL模板
     */
    const ENDPOINT_URL_TEMPLATE = 'https://%s.253.com/msg/send/json';

    /**
     * 国际短信
     */
    const INT_URL = 'http://intapi.253.com/send/json';

    /**
     * 验证码渠道code.
     */
    const CHANNEL_VALIDATE_CODE = 'smsbj1';

    /**
     * 会员营销渠道code.
     */
    const CHANNEL_PROMOTION_CODE = 'smssh1';

    /**
     * @param PhoneNumberInterface $to
     * @param MessageInterface     $message
     * @param Config               $config
     *
     * @return array
     *
     * @throws GatewayErrorException
     * @throws InvalidArgumentException
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $IDDCode = !empty($to->getIDDCode()) ? $to->getIDDCode() : 86;

        $params = [
            'account' => $config->get('account'),
            'password' => $config->get('password'),
            'phone' => $to->getNumber(),
            'msg' => $this->wrapChannelContent($message->getContent($this), $config, $IDDCode),
        ];

        if (86 != $IDDCode) {
            $params['mobile'] = $to->getIDDCode().$to->getNumber();
            $params['account'] = $config->get('intel_account') ?: $config->get('account');
            $params['password'] = $config->get('intel_password') ?: $config->get('password');
        }

        $result = $this->postJson($this->buildEndpoint($config, $IDDCode), $params);

        if (!isset($result['code']) || '0' != $result['code']) {
            throw new GatewayErrorException(json_encode($result, JSON_UNESCAPED_UNICODE), isset($result['code']) ? $result['code'] : 0, $result);
        }

        return $result;
    }

    /**
     * @param Config $config
     * @param int    $IDDCode
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function buildEndpoint(Config $config, $IDDCode = 86)
    {
        $channel = $this->getChannel($config, $IDDCode);

        if (self::INT_URL === $channel) {
            return $channel;
        }

        return sprintf(self::ENDPOINT_URL_TEMPLATE, $channel);
    }

    /**
     * @param Config $config
     * @param int    $IDDCode
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    protected function getChannel(Config $config, $IDDCode)
    {
        if (86 != $IDDCode) {
            return self::INT_URL;
        }
        $channel = $config->get('channel', self::CHANNEL_VALIDATE_CODE);

        if (!in_array($channel, [self::CHANNEL_VALIDATE_CODE, self::CHANNEL_PROMOTION_CODE])) {
            throw new InvalidArgumentException('Invalid channel for ChuanglanGateway.');
        }

        return $channel;
    }

    /**
     * @param string $content
     * @param Config $config
     * @param int    $IDDCode
     *
     * @return string|string
     *
     * @throws InvalidArgumentException
     */
    protected function wrapChannelContent($content, Config $config, $IDDCode)
    {
        $channel = $this->getChannel($config, $IDDCode);

        if (self::CHANNEL_PROMOTION_CODE == $channel) {
            $sign = (string) $config->get('sign', '');
            if (empty($sign)) {
                throw new InvalidArgumentException('Invalid sign for ChuanglanGateway when using promotion channel');
            }

            $unsubscribe = (string) $config->get('unsubscribe', '');
            if (empty($unsubscribe)) {
                throw new InvalidArgumentException('Invalid unsubscribe for ChuanglanGateway when using promotion channel');
            }

            $content = $sign.$content.$unsubscribe;
        }

        return $content;
    }
}
