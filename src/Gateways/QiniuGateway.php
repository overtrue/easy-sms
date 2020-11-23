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
 * Class QiniuGateway.
 *
 * @see https://developer.qiniu.com/sms/api/5897/sms-api-send-message
 */
class QiniuGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'https://%s.qiniuapi.com/%s/%s';

    const ENDPOINT_VERSION = 'v1';

    /**
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface     $message
     * @param \Overtrue\EasySms\Support\Config                 $config
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\GatewayErrorException ;
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $endpoint = $this->buildEndpoint('sms', 'message/single');

        $data = $message->getData($this);

        $params = [
            'template_id' => $message->getTemplate($this),
            'mobile' => $to->getNumber(),
        ];

        if (!empty($data)) {
            $params['parameters'] = $data;
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $headers['Authorization'] = $this->generateSign($endpoint, 'POST', json_encode($params), $headers['Content-Type'], $config);

        $result = $this->postJson($endpoint, $params, $headers);

        if (isset($result['error'])) {
            throw new GatewayErrorException($result['message'], $result['error'], $result);
        }

        return $result;
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
        return sprintf(self::ENDPOINT_TEMPLATE, $type, self::ENDPOINT_VERSION, $function);
    }

    /**
     * Build endpoint url.
     *
     * @param string $url
     * @param string $method
     * @param string $body
     * @param string $contentType
     * @param Config $config
     *
     * @return string
     */
    protected function generateSign($url, $method, $body, $contentType, Config $config)
    {
        $urlItems = parse_url($url);
        $host = $urlItems['host'];
        if (isset($urlItems['port'])) {
            $port = $urlItems['port'];
        } else {
            $port = '';
        }
        $path = $urlItems['path'];
        if (isset($urlItems['query'])) {
            $query = $urlItems['query'];
        } else {
            $query = '';
        }
        //write request uri
        $toSignStr = $method.' '.$path;
        if (!empty($query)) {
            $toSignStr .= '?'.$query;
        }
        //write host and port
        $toSignStr .= "\nHost: ".$host;
        if (!empty($port)) {
            $toSignStr .= ':'.$port;
        }
        //write content type
        if (!empty($contentType)) {
            $toSignStr .= "\nContent-Type: ".$contentType;
        }
        $toSignStr .= "\n\n";
        //write body
        if (!empty($body)) {
            $toSignStr .= $body;
        }

        $hmac = hash_hmac('sha1', $toSignStr, $config->get('secret_key'), true);

        return 'Qiniu '.$config->get('access_key').':'.$this->base64UrlSafeEncode($hmac);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    protected function base64UrlSafeEncode($data)
    {
        $find = array('+', '/');
        $replace = array('-', '_');

        return str_replace($find, $replace, base64_encode($data));
    }
}
