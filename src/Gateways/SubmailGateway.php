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
 * Class SubmailGateway.
 */
class SubmailGateway extends Gateway
{

    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'https://api.mysubmail.com/message/%s.%s';
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
        $endpoint = $this->buildEndpoint('xsend');
        return $this->post($endpoint, [
            'appid' => $this->config->get('app_id'),
            'signature' => $this->config->get('app_key'),
            'project' => $this->config->get('project'),
            'to' => $to,
            'vars' => json_encode($data),
        ]);
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
}