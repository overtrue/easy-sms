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
 * Class Tiniyo Gateway.
 *
 *  @see https://tiniyo.com/sms.html
 */
class TiniyoGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'https://api.tiniyo.com/v1/Account/%s/Message';

    protected $errorStatuses = [
        'failed',
    ];

    public function getName()
    {
        return 'tiniyo';
    }

    /**
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
        $accountSid = $config->get('account_sid');
        $endpoint = $this->buildEndPoint($accountSid);

        $params = [
            'dst' => $to->getUniversalNumber(),
            'src' => $config->get('from'),
            'text' => $message->getContent($this),
        ];

        try {
            $result = $this->request('post', $endpoint, [
                'auth' => [
                    $accountSid,
                    $config->get('token'),
                ],
                'form_params' => $params,
            ]);
            if (in_array($result['status'], $this->errorStatuses) || !is_null($result['error_code'])) {
                throw new GatewayErrorException($result['message'], $result['error_code'], $result);
            }
        } catch (ClientException $e) {
            throw new GatewayErrorException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * build endpoint url.
     *
     * @param string $accountSid
     *
     * @return string
     */
    protected function buildEndPoint($accountSid)
    {
        return sprintf(self::ENDPOINT_URL, $accountSid);
    }
}
