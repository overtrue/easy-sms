<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) iwzh <wzhec@foxmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class BaiduGateway
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
    const SUCCESS_CODE=1000;
    
    /**
     * Send  message.
     *
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
            'invoke_id' => $config->get('invoke_id'),
            'phoneNumber' => $to,
            'templateCode' => $message->getTemplate(),
            'contentVar' => $message->getData()
        ];
        //默认值当前时间
        date_default_timezone_set("PRC");
        $timestamp = new \DateTime();
        $timestamp->setTimezone(new \DateTimeZone("UTC"));
        $datetime = $timestamp->format("Y-m-d\TH:i:s\Z");
        //生成json格式
        $headers = ['Host'=>self::ENDPOINT_HOST,"Content-Type" => 'application/json',  'x-bce-date' => $datetime, 'x-bce-content-sha256' => hash('sha256', json_encode($params))];
        $headersToSign =array('host', 'x-bce-content-sha256',);//需要签名的header头
        $headers['Authorization']=$this->generateSign($headersToSign, $datetime,$headers,$config);
                
        $result=$this->request('post',self::buildEndpoint(), ['headers' => $headers, 'json' => $params]);
        
        if($result['code']!=self::SUCCESS_CODE){
            throw new GatewayErrorException($result['message'],$result['code'],$result);
        }
        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @return string
     */
    public static function buildEndpoint()
    {
        return 'http://'.self::ENDPOINT_HOST.self::ENDPOINT_URI;
    }
    
    /**
     * generate Authorization.
     *
     * @param $headersToSign
     * @param $datetime
     * @param $headers
     * @param Config $config
     * @return string
     */
    public function generateSign($headersToSign, $datetime, $headers, Config $config)
    {
        //生成authString
        $authString = self::BCE_AUTH_VERSION . '/' . $config->get('ak') . '/'
            . $datetime . '/' . self::DEFAULT_EXPIRATION_IN_SECONDS;
        //使用sk和authString生成signKey
        $signingKey = hash_hmac('sha256', $authString, $config->get('sk'));
        //生成标准化URI
        $canonicalURI = str_replace('%2F', '/', rawurlencode(self::ENDPOINT_URI));// 根据RFC 3986，除了：1.大小写英文字符2.阿拉伯数字3.点'.'、波浪线'~'、减号'-'以及下划线'_' 以外都要编码
        
        //生成标准化QueryString
        $canonicalQueryString = '';//此api不需要此项。返回空字符串
        
        $signHeaders = self::getHeadersToSign($headers, $headersToSign);//获得需要签名的数据
        //整理headersToSign，以';'号连接
        $signedHeaders = empty($signHeaders) ? '' : strtolower(trim(implode(";", array_keys($signHeaders))));
        //生成标准化header
        $canonicalHeader = self::getCanonicalHeaders($signHeaders);
        //组成标准请求串
        $canonicalRequest = "POST\n$canonicalURI\n" . "$canonicalQueryString\n$canonicalHeader";
        //使用signKey和标准请求串完成签名
        $signature = hash_hmac('sha256', $canonicalRequest, $signingKey);
        
        //组成最终签名串
        $authorizationHeader = "$authString/$signedHeaders/$signature";
        return $authorizationHeader;
    }
    
    /**
     * 生成标准化http请求头串
     *
     * @param $headers
     * @return string
     */
    public static function getCanonicalHeaders($headers)
    {
        //如果没有headers，则返回空串
        if (count($headers) == 0) {
            return '';
        }
        
        $headerStrings = array();
        foreach ($headers as $k => $v) {
            //跳过key为null的
            if ($k === null) {
                continue;
            }
            //如果value为null，则赋值为空串
            if ($v === null) {
                $v = '';
            }
            //trim后再encode，之后使用':'号连接起来
            $headerStrings[] = rawurlencode((strtolower(trim($k)))) . ':' . rawurlencode((trim($v)));
        }
        //字典序排序
        sort($headerStrings);
        
        //用'\n'把它们连接起来
        return implode("\n", $headerStrings);
    }
    
    /**
     * 根据headsToSign过滤应该参与签名的header
     *
     * @param $headers
     * @param $headersToSign
     * @return array
     */
    public static function getHeadersToSign($headers, $headersToSign)
    {
        //value被trim后为空串的header不参与签名
        $filter_empty = function ($v) {
            return trim((string)$v) !== '';
        };
        $headers = array_filter($headers, $filter_empty);
        
        //处理headers的key：去掉前后的空白并转化成小写
        $trim_and_lower = function ($str) {
            return strtolower(trim($str));
        };
        $temp = array();
        $process_keys = function ($k, $v) use (&$temp, $trim_and_lower) {
            $temp[$trim_and_lower($k)] = $v;
        };
        array_map($process_keys, array_keys($headers), $headers);
        $headers = $temp;
        
        //取出headers的key以备用
        $header_keys = array_keys($headers);
        //预处理headersToSign：去掉前后的空白并转化成小写
        $headersToSign = array_map($trim_and_lower, $headersToSign);
        
        //只选取在headersToSign里面的header
        $filtered_keys = array_intersect($header_keys, $headersToSign);
        
        //返回需要参与签名的header
        return array_intersect_key($headers, array_flip($filtered_keys));
    }

}
