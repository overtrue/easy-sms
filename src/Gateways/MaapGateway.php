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
 * Class MaapGateway.
 *
 * @see https://maap.wo.cn/
 */
class MaapGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'http://rcsapi.wo.cn:8000/umcinterface/sendtempletmsg';

    /**
     * Send message.
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface     $message
     * @param \Overtrue\EasySms\Support\Config                 $config
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\GatewayErrorException
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $params = [
            'cpcode' => $config->get('cpcode'),
            'msg' => implode(',', $message->getData($this)),
            'mobiles' => $to->getNumber(),
            'excode' => $config->get('excode', ''),
            'templetid' => $message->getTemplate($this),
        ];
        $params['sign'] = $this->generateSign($params, $config->get('key'));

        $result = $this->postJson(self::ENDPOINT_URL, $params);

        if (0 != $result['resultcode']) {
            throw new GatewayErrorException($result['resultmsg'], $result['resultcode'], $result);
        }

        return $result;
    }

    /**
     * Generate Sign.
     *
     * @param array $params
     * @param string $key 签名Key
     * @return string
     */
    protected function generateSign($params, $key)
    {
        return md5($params['cpcode'] . $params['msg'] . $params['mobiles'] . $params['excode'] . $params['templetid'] . $key);
    }
}