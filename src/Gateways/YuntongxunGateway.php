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
 * Class YuntongxunGateway.
 *
 * @see Chinese Mainland: http://doc.yuntongxun.com/pe/5a533de33b8496dd00dce07c
 * @see International: http://doc.yuntongxun.com/pe/604f29eda80948a1006e928d
 */
class YuntongxunGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'https://%s:%s/%s/%s/%s/%s/%s?sig=%s';

    const SERVER_IP = 'app.cloopen.com';

    const DEBUG_SERVER_IP = 'sandboxapp.cloopen.com';

    const DEBUG_TEMPLATE_ID = 1;

    const SERVER_PORT = '8883';

    const SDK_VERSION = '2013-12-26';

    const SDK_VERSION_INT = 'v2';

    const SUCCESS_CODE = '000000';

    private $international = false; // if international SMS, default false means no.

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
        $datetime = date('YmdHis');

        $data = [
            'appId' => $config->get('app_id'),
        ];

        if ($to->inChineseMainland()) {
            $type = 'SMS';
            $resource = 'TemplateSMS';
            $data['to'] = $to->getNumber();
            $data['templateId'] = (int) ($this->config->get('debug') ? self::DEBUG_TEMPLATE_ID : $message->getTemplate($this));
            $data['datas'] = $message->getData($this);
        } else {
            $type = 'international';
            $resource = 'send';
            $this->international = true;
            $data['mobile'] = $to->getZeroPrefixedNumber();
            $data['content'] = $message->getContent($this);
        }

        $endpoint = $this->buildEndpoint($type, $resource, $datetime, $config);

        $result = $this->request('post', $endpoint, [
            'json' => $data,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json;charset=utf-8',
                'Authorization' => base64_encode($config->get('account_sid').':'.$datetime),
            ],
        ]);

        if (self::SUCCESS_CODE != $result['statusCode']) {
            throw new GatewayErrorException($result['statusCode'], $result['statusCode'], $result);
        }

        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @param string                           $type
     * @param string                           $resource
     * @param string                           $datetime
     * @param \Overtrue\EasySms\Support\Config $config
     *
     * @return string
     */
    protected function buildEndpoint($type, $resource, $datetime, Config $config)
    {
        $serverIp = $this->config->get('debug') ? self::DEBUG_SERVER_IP : self::SERVER_IP;

        if ($this->international) {
            $accountType = 'account';
            $sdkVersion = self::SDK_VERSION_INT;
        } else {
            $accountType = $this->config->get('is_sub_account') ? 'SubAccounts' : 'Accounts';
            $sdkVersion = self::SDK_VERSION;
        }

        $sig = strtoupper(md5($config->get('account_sid').$config->get('account_token').$datetime));

        return sprintf(self::ENDPOINT_TEMPLATE, $serverIp, self::SERVER_PORT, $sdkVersion, $accountType, $config->get('account_sid'), $type, $resource, $sig);
    }
}
