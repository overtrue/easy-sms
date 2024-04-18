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
 * Class SubmailGateway.
 *
 * @see https://www.mysubmail.com/chs/documents/developer/index
 */
class SubmailGateway extends Gateway
{
    use HasHttpRequest;

    public const ENDPOINT_TEMPLATE = 'https://api.mysubmail.com/%s.%s';

    public const ENDPOINT_FORMAT = 'json';

    /**
     * @return array
     *
     * @throws GatewayErrorException ;
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $isContent = (bool) $message->getContent($this);
        if ($isContent) {
            $endpoint = $this->buildEndpoint($this->inChineseMainland($to) ? 'sms/send' : 'internationalsms/send');
            $params = [
                'appid' => $config->get('app_id'),
                'signature' => $config->get('app_key'),
                'content' => $message->getContent($this),
                'to' => $to->getUniversalNumber(),
            ];
        } else {
            $endpoint = $this->buildEndpoint($this->inChineseMainland($to) ? 'message/xsend' : 'internationalsms/xsend');
            $data = $message->getData($this);
            $template_code = $message->getTemplate($this);
            $params = [
                'appid' => $config->get('app_id'),
                'signature' => $config->get('app_key'),
                'project' => !empty($template_code) ? $template_code : (!empty($data['project']) ? $data['project'] : $config->get('project')),
                'to' => $to->getUniversalNumber(),
                'vars' => json_encode($data, JSON_FORCE_OBJECT),
            ];
        }

        $result = $this->post($endpoint, $params);

        if ('success' != $result['status']) {
            throw new GatewayErrorException($result['msg'], $result['code'], $result);
        }

        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @param string $function
     *
     * @return string
     */
    protected function buildEndpoint($function)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $function, self::ENDPOINT_FORMAT);
    }

    /**
     * Check if the phone number belongs to chinese mainland.
     *
     * @param PhoneNumberInterface $to
     *
     * @return bool
     */
    protected function inChineseMainland($to)
    {
        $code = $to->getIDDCode();

        return empty($code) || 86 === $code;
    }
}
