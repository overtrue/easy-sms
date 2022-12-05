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

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;
use Psr\Http\Message\RequestInterface;

/**
 * Class VolcengineGateway.
 *
 * @see https://www.volcengine.com/docs/6361/66704
 */
class VolcengineGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_ACTION = 'SendSms';
    const ENDPOINT_VERSION = '2020-01-01';
    const ENDPOINT_CONTENT_TYPE = 'application/json; charset=utf-8';
    const ENDPOINT_ACCEPT = 'application/json';
    const ENDPOINT_USER_AGENT = 'overtrue/easy-sms';
    const ENDPOINT_SERVICE = 'volcSMS';

    const Algorithm = 'HMAC-SHA256';

    const ENDPOINT_DEFAULT_REGION_ID = 'cn-north-1';

    public static $endpoints = [
        'cn-north-1' => 'https://sms.volcengineapi.com',
        'ap-singapore-1' => 'https://sms.byteplusapi.com',
    ];

    private $regionId = self::ENDPOINT_DEFAULT_REGION_ID;
    protected $requestDate;


    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $data = $message->getData($this);
        $signName = !empty($data['sign_name']) ? $data['sign_name'] : $config->get('sign_name');
        $smsAccount = !empty($data['sms_account']) ? $data['sms_account'] : $config->get('sms_account');
        $templateId = $message->getTemplate($this);
        $phoneNumbers = !empty($data['phone_numbers']) ? $data['phone_numbers'] : $to->getNumber();
        $templateParam = !empty($data['template_param']) ? $data['template_param'] : $message->getData($this);

        $tag = !empty($data['tag']) ? $data['tag'] : '';

        $payload = [
            'SmsAccount' => $smsAccount, // 消息组帐号,火山短信页面右上角，短信应用括号中的字符串
            'Sign' => $signName, // 短信签名
            'TemplateID' => $templateId, // 短信模板ID
            'TemplateParam' => json_encode($templateParam), // 短信模板占位符要替换的值
            'PhoneNumbers' => $phoneNumbers, // 手机号，如果有多个使用英文逗号分割
        ];
        if ($tag) {
            $payload['Tag'] = $tag;
        }
        $queries = [
            'Action' => self::ENDPOINT_ACTION,
            'Version' => self::ENDPOINT_VERSION,
        ];

        try {
            $stack = HandlerStack::create();
            $stack->push($this->signHandle());
            $this->setGuzzleOptions([
                'headers' => [
                    'Content-Type' => self::ENDPOINT_CONTENT_TYPE,
                    'Accept' => self::ENDPOINT_ACCEPT,
                    'User-Agent' => self::ENDPOINT_USER_AGENT
                ],
                'timeout' => $this->getTimeout(),
                'handler' => $stack,
                'base_uri' => $this->getEndpoint(),
            ]);

            $response = $this->request('post', $this->getEndpoint().$this->getCanonicalURI(), [
                'query' => $queries,
                'json' => $payload,
            ]);
            if ($response instanceof Psr7\Response) {
                $response = json_decode($response->getBody()->getContents(), true);
            }
            if (isset($response['ResponseMetadata']['Error'])) { // 请求错误参数，如果请求没有错误，则不存在该参数返回
                // 火山引擎错误码格式为：'ZJ'+ 5位数字，比如 ZJ20009，取出数字部分
                preg_match('/\d+/', $response['ResponseMetadata']['Error']['Code'], $matches);
                throw new GatewayErrorException($response['ResponseMetadata']['Error']['Code'].":".$response['ResponseMetadata']['Error']['Message'], $matches[0], $response);
            }
            return $response;
        } catch (ClientException $exception) {
            $responseContent = $exception->getResponse()->getBody()->getContents();
            $response = json_decode($responseContent, true);
            if (isset($response['ResponseMetadata']['Error']) && $error = $response['ResponseMetadata']['Error']) { // 请求错误参数，如果请求没有错误，则不存在该参数返回
                // 火山引擎公共错误码Error与业务错误码略有不同，比如："Error":{"CodeN":100004,"Code":"MissingRequestInfo","Message":"The request is missing timestamp information."}
                // 此处错误码直接取 CodeN
                throw new GatewayErrorException($error["CodeN"].":".$error['Message'], $error["CodeN"], $response);
            }
            throw new GatewayErrorException($responseContent, $exception->getCode(), ['content' => $responseContent]);
        }
    }

    protected function signHandle()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $request = $request->withHeader('X-Date', $this->getRequestDate());
                list($canonicalHeaders, $signedHeaders) = $this->getCanonicalHeaders($request);

                $queries = Query::parse($request->getUri()->getQuery());
                $canonicalRequest = $request->getMethod()."\n"
                    .$this->getCanonicalURI()."\n"
                    .$this->getCanonicalQueryString($queries)."\n"
                    .$canonicalHeaders."\n"
                    .$signedHeaders."\n"
                    .$this->getPayloadHash($request);

                $stringToSign = $this->getStringToSign($canonicalRequest);

                $signingKey = $this->getSigningKey();

                $signature = hash_hmac('sha256', $stringToSign, $signingKey);
                $parsed = $this->parseRequest($request);

                $parsed['headers']['Authorization'] = self::Algorithm.
                    ' Credential='.$this->getAccessKeyId().'/'.$this->getCredentialScope().', SignedHeaders='.$signedHeaders.', Signature='.$signature;

                $buildRequest = function () use ($request, $parsed) {
                    if ($parsed['query']) {
                        $parsed['uri'] = $parsed['uri']->withQuery(Query::build($parsed['query']));
                    }

                    return new Psr7\Request(
                        $parsed['method'],
                        $parsed['uri'],
                        $parsed['headers'],
                        $parsed['body'],
                        $parsed['version']
                    );
                };

                return $handler($buildRequest(), $options);
            };
        };
    }

    private function parseRequest(RequestInterface $request)
    {
        $uri = $request->getUri();
        return [
            'method' => $request->getMethod(),
            'path' => $uri->getPath(),
            'query' => Query::parse($uri->getQuery()),
            'uri' => $uri,
            'headers' => $request->getHeaders(),
            'body' => $request->getBody(),
            'version' => $request->getProtocolVersion()
        ];
    }

    public function getPayloadHash(RequestInterface $request)
    {
        if ($request->hasHeader('X-Content-Sha256')) {
            return $request->getHeaderLine('X-Content-Sha256');
        }

        return Utils::hash($request->getBody(), 'sha256');
    }

    public function getRegionId()
    {
        return $this->config->get('region_id', self::ENDPOINT_DEFAULT_REGION_ID);
    }

    public function getEndpoint()
    {
        $regionId = $this->getRegionId();
        if (!in_array($regionId, array_keys(self::$endpoints))) {
            $regionId = self::ENDPOINT_DEFAULT_REGION_ID;
        }
        return static::$endpoints[$regionId];
    }

    public function getRequestDate()
    {
        return $this->requestDate ?: gmdate('Ymd\THis\Z');
    }


    /**
     * 指代信任状，格式为：YYYYMMDD/region/service/request
     * @return string
     */
    public function getCredentialScope()
    {
        return date('Ymd', strtotime($this->getRequestDate())).'/'.$this->getRegionId().'/'.self::ENDPOINT_SERVICE.'/request';
    }

    /**
     * 计算签名密钥
     * 在计算签名前，首先从私有访问密钥（Secret Access Key）派生出签名密钥（signing key），而不是直接使用私有访问密钥。具体计算过程如下：
     * kSecret = *Your Secret Access Key*
     * kDate = HMAC(kSecret, Date)
     * kRegion = HMAC(kDate, Region)
     * kService = HMAC(kRegion, Service)
     * kSigning = HMAC(kService, "request")
     * 其中Date精确到日，与RequestDate中YYYYMMDD部分相同。
     * @return string
     */
    protected function getSigningKey()
    {
        $dateKey = hash_hmac('sha256', date("Ymd", strtotime($this->getRequestDate())), $this->getAccessKeySecret(), true);
        $regionKey = hash_hmac('sha256', $this->getRegionId(), $dateKey, true);
        $serviceKey = hash_hmac('sha256', self::ENDPOINT_SERVICE, $regionKey, true);
        return hash_hmac('sha256', 'request', $serviceKey, true);
    }

    /**
     * 创建签名字符串
     * 签名字符串主要包含请求以及正规化请求的元数据信息，由签名算法、请求日期、信任状和正规化请求哈希值连接组成，伪代码如下：
     * StringToSign = Algorithm + '\n' + RequestDate + '\n' + CredentialScope + '\n' + HexEncode(Hash(CanonicalRequest))
     * @return string
     */
    public function getStringToSign($canonicalRequest)
    {
        return self::Algorithm."\n".$this->getRequestDate()."\n".$this->getCredentialScope()."\n".hash('sha256', $canonicalRequest);
    }

    /**
     * @return string
     */
    public function getAccessKeySecret()
    {
        return $this->config->get('access_key_secret');
    }

    /**
     * @return string
     */
    public function getAccessKeyId()
    {
        return $this->config->get('access_key_id');
    }

    /**
     * 指代正规化后的Header。
     * 其中伪代码如下：
     * CanonicalHeaders = CanonicalHeadersEntry0 + CanonicalHeadersEntry1 + ... + CanonicalHeadersEntryN
     * 其中CanonicalHeadersEntry = Lowercase(HeaderName) + ':' + Trimall(HeaderValue) + '\n'
     * Lowcase代表将Header的名称全部转化成小写，Trimall表示去掉Header的值的前后多余的空格。
     * 特别注意：最后需要添加"\n"的换行符，header的顺序是以headerName的小写后ascii排序。
     * @return array
     */
    public function getCanonicalHeaders(RequestInterface $request)
    {
        $headers = $request->getHeaders();
        ksort($headers);
        $canonicalHeaders = '';
        $signedHeaders = [];
        foreach ($headers as $key => $val) {
            $lowerKey = strtolower($key);
            $canonicalHeaders .= $lowerKey.':'.trim($val[0]).PHP_EOL;
            $signedHeaders[] = $lowerKey;
        }
        $signedHeadersString = implode(';', $signedHeaders);
        return [$canonicalHeaders, $signedHeadersString];
    }

    /**
     * urlencode（注：同RFC3986方法）每一个querystring参数名称和参数值。
     * 按照ASCII字节顺序对参数名称严格排序，相同参数名的不同参数值需保持请求的原始顺序。
     * 将排序好的参数名称和参数值用=连接，按照排序结果将“参数对”用&连接。
     * 例如：CanonicalQueryString = "Action=ListUsers&Version=2018-01-01"
     * @return string
     */
    public function getCanonicalQueryString(array $query)
    {
        ksort($query);
        return http_build_query($query);
    }

    /**
     * 指代正规化后的URI。
     * 如果URI为空，那么使用"/"作为绝对路径。
     * 在火山引擎中绝大多数接口的URI都为"/"。
     * 如果是复杂的path，请通过RFC3986规范进行编码。
     *
     * @return string
     */
    public function getCanonicalURI()
    {
        return '/';
    }
}
