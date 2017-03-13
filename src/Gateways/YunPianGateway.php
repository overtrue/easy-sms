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
 * Class YunPianGateway.
 */
class YunPianGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'https://%s.yunpian.com/%s/%s/%s.%s';
    const ENDPOINT_VERSION = 'v2';
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
        $endpoint = $this->buildEndpoint('sms', 'sms', 'single_send');

        return $this->post($endpoint, [
            'apikey' => $this->config->get('api_key'),
            'mobile' => $to,
            'text' => $message,
        ]);
    }

    /**
     * Build endpoint url.
     *
     * @param string $type
     * @param string $resource
     * @param string $function
     *
     * @return string
     */
    protected function buildEndpoint($type, $resource, $function)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $type, self::ENDPOINT_VERSION, $resource, $function, self::ENDPOINT_FORMAT);
    }
}
