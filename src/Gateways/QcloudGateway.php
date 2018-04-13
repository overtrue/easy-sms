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
 * Class AlidayuGateway.
 *
 * @see https://yun.tim.qq.com/v5/tlssmssvr/sendsms?sdkappid=xxxxx&random=xxxx
 */
class QcloudGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'https://yun.tim.qq.com/v5/';

    const ENDPOINT_METHOD = 'tlssmssvr/sendsms';

    const ENDPOINT_VERSION = 'v5';

    const ENDPOINT_FORMAT = 'json';

    /**
     * Get gateway name.
     *
     * @return string
     */
    public function getName()
    {
        return 'qcloud';
    }

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
            'tel' => [
                'nationcode' => $message->getData($this)['nationcode'] ?? '86',
                'mobile' => $to,
            ],
            'type' => $message->getData($this)['type'] ?? 0,
            'msg' => $message->getContent($this),
            'time' => time(),
            'extend' => '',
            'ext' => '',
        ];

        $random = substr(uniqid(), -10);

        $params['sig'] = $this->generateSign($params, $random);

        $url = self::ENDPOINT_URL.self::ENDPOINT_METHOD.'?sdkappid='.$config->get('sdk_app_id').'&random='.$random;

        $result = $this->request('post', $url, [
            'headers' => ['Accept' => 'application/json'],
            'json' => $params,
        ]);

        if (0 != $result['result']) {
            throw new GatewayErrorException($result['errmsg'], $result['result'], $result);
        }

        return $result;
    }

    /**
     * Generate Sign.
     *
     * @param array  $params
     * @param string $random
     *
     * @return string
     */
    protected function generateSign($params, $random)
    {
        ksort($params);

        return hash('sha256', sprintf(
            'appkey=%s&random=%s&time=%s&mobile=%s',
            $this->config->get('app_key'),
            $random,
            $params['time'],
            $params['tel']['mobile']
        ), false);
    }
}
