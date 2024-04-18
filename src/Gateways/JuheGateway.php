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
 * Class JuheGateway.
 *
 * @see https://www.juhe.cn/docs/api/id/54
 */
class JuheGateway extends Gateway
{
    use HasHttpRequest;

    public const ENDPOINT_URL = 'http://v.juhe.cn/sms/send';

    public const ENDPOINT_FORMAT = 'json';

    /**
     * @return array
     *
     * @throws GatewayErrorException ;
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $params = [
            'mobile' => $to->getNumber(),
            'tpl_id' => $message->getTemplate($this),
            'tpl_value' => $this->formatTemplateVars($message->getData($this)),
            'dtype' => self::ENDPOINT_FORMAT,
            'key' => $config->get('app_key'),
        ];

        $result = $this->get(self::ENDPOINT_URL, $params);

        if ($result['error_code']) {
            throw new GatewayErrorException($result['reason'], $result['error_code'], $result);
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function formatTemplateVars(array $vars)
    {
        $formatted = [];

        foreach ($vars as $key => $value) {
            $formatted[sprintf('#%s#', trim($key, '#'))] = $value;
        }

        return urldecode(http_build_query($formatted));
    }
}
