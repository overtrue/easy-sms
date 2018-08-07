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
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class MwGateway.
 *
 * @see hhttp://con.monyun.cn:9960/developer_Center/index.html?htmlURL1=API&htmlURL2=APIone&iden=1334141369429928360
 */
class MwGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'http://61.145.229.28:8806/MWGate/wmgw.asmx/MongateSendSubmit';

    /**
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface $message
     * @param \Overtrue\EasySms\Support\Config $config
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\GatewayErrorException ;
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $params = [
            'query' =>
                ['userId' => $config->get('userId'),
                    'password' => $config->get('password'),
                    'pszMobis' => $to->getUniversalNumber(),
                    'pszMsg' => $message->getContent($this),
                    'iMobiCount' => 1,
                    'pszSubPort' => $config->get('pszSubPort')
                ]
        ];
        $result = $this->request('get', self::ENDPOINT_TEMPLATE, $params);
        $temp = [];
        if (isset($result['code'])) {
            return $result;
        }
        if (abs(intval($result[0])) > 100000) {
            $temp['code'] = 1;
            $temp['msg'] = 'success';
        } else {
            $temp['code'] = -1;
            $temp['msg'] = 'error';
        }
        $temp['real_code'] = $result[0];
        return $temp;
    }
}
