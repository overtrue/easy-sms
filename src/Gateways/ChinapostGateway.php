<?php

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * @author Success Go
 * @see http://dx.11185.cn/isms-doc/
 */
class ChinapostGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT = 'https://dx.11185.cn:13011/isms-send-api/send/sms';

    const SUCCESS_CODE = 200;

    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $data = $message->getData();
        $form_params = [
            'phone' => $to->getNumber(),
            'templateId' => $message->getTemplate(),
            'varValues' => isset($data['varValues']) ? $data['varValues'] : '{}',
            'extendNum' => isset($data['extendNum']) ? $data['extendNum'] : '',
        ];
        $headers = [
            'appKey' => $config->get('AppKey'),
            'appSecret' => $config->get('AppSecret'),
        ];
        $result = $this->post(self::ENDPOINT, $form_params, $headers);

        if (!$this->isSuccess($result)) {
            $errorCode = '';
            if (isset($result['errorCode'])) {
                $errorCode = $result['errorCode'];
            }
            $errorMessage = '其他错误';
            if (isset($result['errorMessage'])) {
                $errorMessage = $result['errorMessage'];
            }
            throw new GatewayErrorException($errorMessage, $errorCode, $result);
        }

        return $result;
    }

    private function isSuccess($result)
    {
        if (isset($result['errorCode']) && self::SUCCESS_CODE === $result['errorCode']) {
            return true;
        }
        return false;
    }
}