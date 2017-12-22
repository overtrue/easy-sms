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

    private $action;

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
        if (array_key_exists('action', $message->getData())) {
            $action = $message->getData()['action'];
        } else {
            $action = self::ENDPOINT_ACTION;
        }
        $endpoint = $this->buildEndpoint($action);

        srand((float) microtime() * 1000000);
        $nonce = rand();
        $timestamp = time();

        $headers = [
            'Nonce' => $nonce,
            'App-Key' => $config->get('app_key'),
            'Timestamp' => $timestamp,
            ];
        $signature = $this->generateSign($this->getHeadersToSign($headers, ['Nonce', 'Timestamp']), $config);
        $headers['Signature'] = $signature;

        switch ($action) {
            case 'sendCode':
                $params = [
                    'mobile' => $to,
                    'region' => self::ENDPOINT_REGION,
                    'templateId' => $message->getTemplate($this),
                ];

                break;
            case 'verifyCode':
                if (! array_key_exists('code', $message->getData())
                    or ! array_key_exists('sessionId', $message->getData())) {
                    throw new GatewayErrorException('"code" or "sessionId" is not set', 0);
                }
                $params = [
                    'code' => $message->getData()['code'],
                    'sessionId' => $message->getData()['sessionId'],
                ];

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
            return new GatewayErrorException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * Generate Sign.
     *
     * @param array $params
     *
     * @return string
     */
    protected function generateSign($params, Config $config)
    {
        return sha1(sprintf('%s%s%s', $config->get('app_secret'), $params['Nonce'], $params['Timestamp']));
    }

    /**
     * Get the headers to sign.
     *
     * @param array $headers
     * @param array $keys
     *
     * @return array
     */
    protected function getHeadersToSign(array $headers, array $keys)
    {
        return array_intersect_key($headers, array_flip($keys));
    }

    /**
     * Build endpoint url.
     *
     * @param string $type
     * @param string $resource
     * @param string $function
     *
     * @return string
     */
    protected function buildEndpoint($action)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $action, self::ENDPOINT_FORMAT);
    }
}
