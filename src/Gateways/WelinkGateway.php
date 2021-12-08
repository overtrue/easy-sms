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
 * Class Welink Gateway.
 *
 *  @see https://tiniyo.com/sms.html
 */
class WelinkGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'https://api.51welink.com/EncryptionSubmit/SendSms.ashx';

    const SUCCESS_CODE = 'succ';
    
    public function getName()
    {
        return 'welink';
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
        $accountSid = $config->get('account');
        $password = $config->get('password');

        $encrypt_pwd = strtoupper(\md5($password . 'SMmsEncryptKey'));
        $rand = \mt_rand();
        $now = time();

        $accessKey = hash('sha256', "AccountId={$accountSid}&PhoneNos={$to->getNumber()}&Password={$encrypt_pwd}&Random={$rand}&Timestamp={$now}");

        $params = [
            'AccountId' => $accountSid,
            'AccessKey' => $accessKey,
            'Timestamp' => $now,
            'Random' => $rand,
            'ProductId' => $message->getTemplate($this),
            'PhoneNos' => $to->getNumber(),
            'Content' => $message->getContent($this),
        ];

        $result = $this->request('post', self::ENDPOINT_URL, [
            'json' => $params,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json;charset=utf-8'
            ],
        ]);

        if (self::SUCCESS_CODE != $result['Result']) {
            throw new GatewayErrorException($result['Reason'], 500, $result);
        }

        return $result;
    }
}
