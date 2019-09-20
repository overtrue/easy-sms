<?php

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class KingttoGateWay
 *
 * @see http://www.kingtto.cn/
 */
class KingttoGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'http://101.201.41.194:9999/sms.aspx';

    const ENDPOINT_METHOD = 'send';

    /**
     * @param PhoneNumberInterface $to
     * @param MessageInterface $message
     * @param Config $config
     * @return array|void
     * @throws GatewayErrorException
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $params = [
            'action'   => self::ENDPOINT_METHOD,
            'userid'   => $config->get('userid'),
            'account'  => $config->get('account'),
            'password' => $config->get('password'),
            'mobile'   => $to->getNumber(),
            'content'  => $message->getContent(),
        ];

        $result = $this->post(self::ENDPOINT_URL, $params);

        if ('Success' != $result['returnstatus']) {
            throw new GatewayErrorException($result['message'], $result['remainpoint'], $result);
        }

        return $result;
    }
}
