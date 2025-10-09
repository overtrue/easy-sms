<?php

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * 微趣云短信网关.
 */
class WeiqucloudGateway extends Gateway
{
    use HasHttpRequest;

    public const ENDPOINT_URL = 'http://smsapi.weiqucloud.com/sms/httpSmsInterface2';

    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $data = [
            'userId' => $config->get('userId'),
            'account' => $config->get('account'),
            'password' => $config->get('password'),
            'mobile' => $to->getNumber(),
            'content' => $message->getContent($this),
            'sendTime' => '',
            'action' => 'sendhy',
        ];
        $result = $this->postJson(self::ENDPOINT_URL, $data);

        if (200 === $result['code'] && 'Success' === $result['data']['status']) {
            return $result;
        }
        throw new GatewayErrorException("短信发送失败: {$result['data']['message']}, remainPoint: {$result['data']['remainPoint']}, taskID:{$result['data']['taskID']}", 500, $result);
    }
}
