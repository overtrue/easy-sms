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

use GuzzleHttp\Exception\ClientException;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class RongcloudGateway.
 *
 * @author Darren Gao <realgaodacheng@gmail.com>
 *
 * @see http://www.rongcloud.cn/docs/sms_service.html#send_sms_code
 */
class RongcloudGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'http://api.sms.ronghub.com/%s.%s';

    const ENDPOINT_ACTION = 'sendCode';

    const ENDPOINT_FORMAT = 'json';

    const ENDPOINT_REGION = '86';  // 中国区，目前只支持此国别

    const SUCCESS_CODE = 200;

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
        $data = $message->getData();
        $action = array_key_exists('action', $data) ? $data['action'] : self::ENDPOINT_ACTION;
        $endpoint = $this->buildEndpoint($action);

        $headers = [
            'Nonce' => uniqid(),
            'App-Key' => $config->get('app_key'),
            'Timestamp' => time(),
        ];
        $headers['Signature'] = $this->generateSign($headers, $config);

        switch ($action) {
            case 'sendCode':
                $params = [
                    'mobile' => $to->getNumber(),
                    'region' => self::ENDPOINT_REGION,
                    'templateId' => $message->getTemplate($this),
                ];

                break;
            case 'verifyCode':
                if (!array_key_exists('code', $data)
                    or !array_key_exists('sessionId', $data)) {
                    throw new GatewayErrorException('"code" or "sessionId" is not set', 0);
                }
                $params = [
                    'code' => $data['code'],
                    'sessionId' => $data['sessionId'],
                ];

                break;
            case 'sendNotify':
                $params = [
                    'mobile' => $to->getNumber(),
                    'region' => self::ENDPOINT_REGION,
                    'templateId' => $message->getTemplate($this),
                    ];
                $params = array_merge($params, $data);

                break;
            default:
                throw new GatewayErrorException(sprintf('action: %s not supported', $action));
        }

        try {
            $result = $this->post($endpoint, $params, $headers);

            if (self::SUCCESS_CODE !== $result['code']) {
                throw new GatewayErrorException($result['errorMessage'], $result['code'], $result);
            }
        } catch (ClientException $e) {
            throw new GatewayErrorException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * Generate Sign.
     *
     * @param array                            $params
     * @param \Overtrue\EasySms\Support\Config $config
     *
     * @return string
     */
    protected function generateSign($params, Config $config)
    {
        return sha1(sprintf('%s%s%s', $config->get('app_secret'), $params['Nonce'], $params['Timestamp']));
    }

    /**
     * Build endpoint url.
     *
     * @param string $action
     *
     * @return string
     */
    protected function buildEndpoint($action)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $action, self::ENDPOINT_FORMAT);
    }
}
