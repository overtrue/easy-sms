<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) Jiajian Chan <changejian@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\HasHttpRequest;

/**
 * Class LuosimaoGateway.
 */
class LuosimaoGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'https://%s.luosimao.com/%s/%s.%s';
    const ENDPOINT_VERSION = 'v1';
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
        $endpoint = $this->buildEndpoint('sms-api', 'send');

        return $this->post($endpoint, [
            'mobile' => $to,
            'message' => $message,
        ], [
            'Authorization' => 'Basic ' . base64_encode('api:key-' . $this->config->get('api_key')),
        ]);
    }

    /**
     * Build endpoint url.
     *
     * @param string $type
     * @param string $function
     *
     * @return string
     */
    protected function buildEndpoint($type, $function)
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $type, self::ENDPOINT_VERSION, $function, self::ENDPOINT_FORMAT);
    }
}
