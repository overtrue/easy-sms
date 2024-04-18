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
 * Class TinreeGateway.
 *
 * @see http://cms.tinree.com
 */
class TinreeGateway extends Gateway
{
    use HasHttpRequest;

    public const ENDPOINT_URL = 'http://api.tinree.com/api/v2/single_send';

    /**
     * @return array
     *
     * @throws GatewayErrorException
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $params = [
            'accesskey' => $config->get('accesskey'),
            'secret' => $config->get('secret'),
            'sign' => $config->get('sign'),
            'templateId' => $message->getTemplate($this),
            'mobile' => $to->getNumber(),
            'content' => $this->buildContent($message),
        ];

        $result = $this->post(self::ENDPOINT_URL, $params);

        if (0 != $result['code']) {
            throw new GatewayErrorException($result['msg'], $result['code'], $result);
        }

        return $result;
    }

    /**
     * 构建发送内容
     * 用 data 数据合成内容，或者直接使用 data 的值
     *
     * @return string
     */
    protected function buildContent(MessageInterface $message)
    {
        $data = $message->getData($this);

        if (is_array($data)) {
            return implode('##', $data);
        }

        return $data;
    }
}
