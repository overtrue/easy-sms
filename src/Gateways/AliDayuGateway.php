<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\HasHttpRequest;

/**
 * Class AliDayuGateway.
 */
class AliDayuGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'https://eco.taobao.com/router/rest';
    const ENDPOINT_METHOD = 'alibaba.aliqin.fc.sms.num.send';
    const ENDPOINT_VERSION = '2.0';
    const ENDPOINT_FORMAT = 'json';

    /**
     * Send a short message.
     *
     * @param string|int $to
     * @param string     $message
     * @param array      $data
     *
     * @return mixed
     */
    public function send($to, $message, array $data = [])
    {
        $reqParams = [
            'method' => self::ENDPOINT_METHOD,
            'format' => self::ENDPOINT_FORMAT,
            'v' => self::ENDPOINT_VERSION,
            'sign_method' => 'md5',
            'timestamp' => date("Y-m-d H:i:s"),
            'sms_type' => 'normal',
            'sms_free_sign_name' => $this->config->get('sign_name'),
            'app_key' => $this->config->get('app_key'),
            'sms_template_code' => $this->config->get('template_code'),
            'rec_num' => strval($to),
            'sms_param' => json_encode($data)
        ];
        $reqParams['sign'] = $this->generateSign($reqParams);
        return $this->post(self::ENDPOINT_URL, $reqParams);
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
        foreach ($params as $k => $v)
        {
            if (is_string($v) && "@" != substr($v, 0, 1))
            {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->config->get('app_secret');
        return strtoupper(md5($stringToBeSigned));
    }
}
