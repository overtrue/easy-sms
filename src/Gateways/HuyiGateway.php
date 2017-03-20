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
 * Class HuyiGateway.
 */
class HuyiGateway extends Gateway
{

    use HasHttpRequest;

    const ENDPOINT_URL = 'http://106.ihuyi.com/webservice/sms.php?method=Submit';
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
        $params = [
            'account' => $this->config->get('APIID'),
            'mobile' => strval($to),
            'content' => $message,
            'time' => time(),
            'format' => self::ENDPOINT_FORMAT
        ];
        $params['password'] = $this->generateSign($params);
        return $this->post(self::ENDPOINT_URL, $params);
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
        return md5($params['account'].$this->config->get('APIKEY').$params['mobile'].$params['content'].$params['time']);
    }

}