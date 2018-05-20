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
 * Class SubmailGateway.
 *
 * @see https://www.mysubmail.com/chs/documents/developer/index
 */
class SubmailGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'https://api.mysubmail.com/%s.%s';

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
        $endpoint = $this->buildEndpoint($to->getIDDCode() ? 'internationalsms/xsend' : 'message/xsend');

        $result = $this->post($endpoint, [
            'appid' => $config->get('app_id'),
            'signature' => $config->get('app_key'),
            'project' => $config->get('project'),
            'to' => $to->getUniversalNumber(),
            'vars' => json_encode($message->getData($this), JSON_FORCE_OBJECT),
        ]);

        if ('success' != $result['status']) {
            throw new GatewayErrorException($result['msg'], $result['code'], $result);
        }

        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @param string $function
     *
     * @return string
     */
    protected function buildEndpoint($function)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $function, self::ENDPOINT_FORMAT);
    }
}
