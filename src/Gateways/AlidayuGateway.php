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
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class AlidayuGateway.
 *
 * @see http://open.taobao.com/doc2/apiDetail?apiId=25450#s2
 */
class AlidayuGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'https://eco.taobao.com/router/rest';

    const ENDPOINT_METHOD = 'alibaba.aliqin.fc.sms.num.send';

    const ENDPOINT_VERSION = '2.0';

    const ENDPOINT_FORMAT = 'json';

    /**
     * Get gateway name.
     *
     * @return string
     */
    public function getName()
    {
        return 'alidayu';
    }

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
        $params = [
            'method' => self::ENDPOINT_METHOD,
            'format' => self::ENDPOINT_FORMAT,
            'v' => self::ENDPOINT_VERSION,
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'sms_type' => 'normal',
            'sms_free_sign_name' => $config->get('sign_name'),
            'app_key' => $config->get('app_key'),
            'sms_template_code' => $message->getTemplate($this),
            'rec_num' => strval($to),
            'sms_param' => json_encode($message->getData($this)),
        ];

        $params['sign'] = $this->generateSign($params);

        $result = $this->post(self::ENDPOINT_URL, $params);

        if (!empty($result['error_response'])) {
            if (isset($result['error_response']['sub_msg'])) {
                $message = $result['error_response']['sub_msg'];
            } else {
                $message = $result['error_response']['msg'];
            }

            throw new GatewayErrorException($message, $result['error_response']['code'], $result);
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
    protected function generateSign($params)
    {
        ksort($params);
        $stringToBeSigned = $this->config->get('app_secret');
        foreach ($params as $key => $value) {
            if (is_string($value) && '@' != substr($value, 0, 1)) {
                $stringToBeSigned .= "$key$value";
            }
        }

        $stringToBeSigned .= $this->config->get('app_secret');

        return strtoupper(md5($stringToBeSigned));
    }
}
