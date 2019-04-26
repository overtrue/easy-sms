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
 * Class BaiduGateway.
 *
 * @see https://cloud.baidu.com/doc/SMS/API.html
 */
class BaiduGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_HOST = 'sms.bj.baidubce.com';

    const ENDPOINT_URI = '/bce/v2/message';

    const BCE_AUTH_VERSION = 'bce-auth-v1';

    const DEFAULT_EXPIRATION_IN_SECONDS = 1800; //签名有效期默认1800秒

    const SUCCESS_CODE = 1000;

    /**
     * Send message.
     *
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
        $params = [
            'invokeId' => $config->get('invoke_id'),
            'phoneNumber' => $to->getNumber(),
            'templateCode' => $message->getTemplate($this),
            'contentVar' => $message->getData($this),
        ];

        $datetime = gmdate('Y-m-d\TH:i:s\Z');

        $headers = [
            'host' => self::ENDPOINT_HOST,
            'content-type' => 'application/json',
            'x-bce-date' => $datetime,
            'x-bce-content-sha256' => hash('sha256', json_encode($params)),
        ];
        //获得需要签名的数据
        $signHeaders = $this->getHeadersToSign($headers, ['host', 'x-bce-content-sha256']);

        $headers['Authorization'] = $this->generateSign($signHeaders, $datetime, $config);

        $result = $this->request('post', self::buildEndpoint($config), ['headers' => $headers, 'json' => $params]);

        if (self::SUCCESS_CODE != $result['code']) {
            throw new GatewayErrorException($result['message'], $result['code'], $result);
        }

        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @param \Overtrue\EasySms\Support\Config $config
     *
     * @return string
     */
    protected function buildEndpoint(Config $config)
    {
        return 'http://'.$config->get('domain', self::ENDPOINT_HOST).self::ENDPOINT_URI;
    }

    /**
     * Generate Authorization header.
     *
     * @param array                            $signHeaders
     * @param int                              $datetime
     * @param \Overtrue\EasySms\Support\Config $config
     *
     * @return string
     */
    protected function generateSign(array $signHeaders, $datetime, Config $config)
    {
        // 生成 authString
        $authString = self::BCE_AUTH_VERSION.'/'.$config->get('ak').'/'
            .$datetime.'/'.self::DEFAULT_EXPIRATION_IN_SECONDS;

        // 使用 sk 和 authString 生成 signKey
        $signingKey = hash_hmac('sha256', $authString, $config->get('sk'));
        // 生成标准化 URI
        // 根据 RFC 3986，除了：1.大小写英文字符 2.阿拉伯数字 3.点'.'、波浪线'~'、减号'-'以及下划线'_' 以外都要编码
        $canonicalURI = str_replace('%2F', '/', rawurlencode(self::ENDPOINT_URI));

        // 生成标准化 QueryString
        $canonicalQueryString = ''; // 此 api 不需要此项。返回空字符串

        // 整理 headersToSign，以 ';' 号连接
        $signedHeaders = empty($signHeaders) ? '' : strtolower(trim(implode(';', array_keys($signHeaders))));

        // 生成标准化 header
        $canonicalHeader = $this->getCanonicalHeaders($signHeaders);

        // 组成标准请求串
        $canonicalRequest = "POST\n{$canonicalURI}\n{$canonicalQueryString}\n{$canonicalHeader}";

        // 使用 signKey 和标准请求串完成签名
        $signature = hash_hmac('sha256', $canonicalRequest, $signingKey);

        // 组成最终签名串
        return "{$authString}/{$signedHeaders}/{$signature}";
    }

    /**
     * 生成标准化 http 请求头串.
     *
     * @param array $headers
     *
     * @return string
     */
    protected function getCanonicalHeaders(array $headers)
    {
        $headerStrings = [];
        foreach ($headers as $name => $value) {
            //trim后再encode，之后使用':'号连接起来
            $headerStrings[] = rawurlencode(strtolower(trim($name))).':'.rawurlencode(trim($value));
        }

        sort($headerStrings);

        return implode("\n", $headerStrings);
    }

    /**
     * 根据 指定的 keys 过滤应该参与签名的 header.
     *
     * @param array $headers
     * @param array $keys
     *
     * @return array
     */
    protected function getHeadersToSign(array $headers, array $keys)
    {
        return array_intersect_key($headers, array_flip($keys));
    }
}
