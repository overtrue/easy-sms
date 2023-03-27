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
 * @see https://www.chuanglan.com/document/6110e57909fd9600010209de/62b3dc1d272e290001af3e75
 */
class Chuanglanv1Gateway extends Gateway
{
    use HasHttpRequest;

    /**
     * 国际短信
     */
    const INT_URL = 'http://intapi.253.com/send/json';

    /**
     * URL模板
     */
    const ENDPOINT_URL_TEMPLATE = 'https://smssh1.253.com/msg/%s/json';

    /**
     * 支持单发、群发短信
     */
    const CHANNEL_NORMAL_CODE = 'v1/send';

    /**
     * 单号码对应单内容批量下发
     */
    const CHANNEL_VARIABLE_CODE = 'variable';

    /**
     * @param PhoneNumberInterface $to
     * @param MessageInterface $message
     * @param Config $config
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
            'report' => $config->get('needstatus') ?? false
        ];

        if (86 != $IDDCode) {
            $params['mobile'] = $to->getIDDCode() . $to->getNumber();
            $params['account'] = $config->get('intel_account') ?: $config->get('account');
            $params['password'] = $config->get('intel_password') ?: $config->get('password');
        }

        if (self::CHANNEL_VARIABLE_CODE == $this->getChannel($config, $IDDCode)) {
            $params['params'] = $message->getData($this);
            $params['msg'] = $this->wrapChannelContent($message->getTemplate($this), $config, $IDDCode);
        } else {
            $params['phone'] = $to->getNumber();
            $params['msg'] = $this->wrapChannelContent($message->getContent($this), $config, $IDDCode);
        }

        $result = $this->postJson($this->buildEndpoint($config, $IDDCode), $params);

        if (!isset($result['code']) || '0' != $result['code']) {
            throw new GatewayErrorException(json_encode($result, JSON_UNESCAPED_UNICODE), isset($result['code']) ? $result['code'] : 0, $result);
        }

        return $result;
    }

    /**
     * @param Config $config
     * @param int $IDDCode
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
     * @param int $IDDCode
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
        $channel = $config->get('channel', self::CHANNEL_NORMAL_CODE);

        if (!in_array($channel, [self::CHANNEL_NORMAL_CODE, self::CHANNEL_VARIABLE_CODE])) {
            throw new InvalidArgumentException('Invalid channel for ChuanglanGateway.');
        }

        return $channel;
    }

    /**
     * @param string $content
     * @param Config $config
     * @param int $IDDCode
     *
     * @return string|string
     *
     * @throws InvalidArgumentException
     */
    protected function wrapChannelContent($content, Config $config, $IDDCode)
    {
        return $content;
    }
}
