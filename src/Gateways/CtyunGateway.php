<?php

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Gateways\Gateway;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class CtyunGateway
 *
 * @see https://www.ctyun.cn/document/10020426/10021544
 */
class CtyunGateway extends Gateway
{
    use HasHttpRequest;

    public const SUCCESS_CODE = 'OK';

    public const ENDPOINT_HOST = 'https://sms-global.ctapi.ctyun.cn';

    /**
     * Send a short message.
     *
     * @return array
     *
     * @throws GatewayErrorException
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $data = $message->getData($this);
        $endpoint = self::ENDPOINT_HOST . '/sms/api/v1';
        return $this->execute($endpoint, [
            'phoneNumber' => (string)$to,
            'templateCode' => $this->config->get('template_code'),
            'templateParam' => '{"code":"' . $data['code'] . '"}',
            'signName' => $this->config->get('sign_name'),
            'action' => 'SendSms'
        ]);
    }


    /**
     * @return array
     *
     * @throws GatewayErrorException
     */
    protected function execute(string $url, array $data)
    {
        $uuid = date('ymdHis', time()) . substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
        $time = date('Ymd', time()) . 'T' . date('His') . 'Z';
        $timeDate = substr($time, 0, 8);

        $body = bin2hex(hash("sha256", json_encode($data), true));
        $query = '';
        $strSignature = "ctyun-eop-request-id:" . $uuid . "\n" . "eop-date:" . $time . "\n" . "\n" . $query . "\n" . $body;

        $kTime = $this->sha256($time, $this->config->get('secret_key'));
        $kAk = $this->sha256($this->config->get('access_key'), $kTime);

        $kDate = $this->sha256($timeDate, $kAk);

        $signature = base64_encode($this->sha256(($strSignature), $kDate));
        $headers['Content-Type'] = 'application/json';
        $headers['ctyun-eop-request-id'] = $uuid;
        $headers['Eop-Authorization'] = $this->config->get('access_key') . ' Headers=ctyun-eop-request-id;' . 'eop-date Signature=' . $signature;
        $headers['eop-date'] = $time;

        $result = $this->postJson($url, $data, $headers);
        if ($result['code'] !== self::SUCCESS_CODE) {
            throw new GatewayErrorException($result['message'], $result['code'], $result);
        }
        return $result;
    }

    public function sha256($str, $pass): string
    {
        return (hash_hmac("sha256", ($str), ($pass), true));
    }

}
