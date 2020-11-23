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
 * Class UcloudGateway.
 */
class UcloudGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'https://api.ucloud.cn';

    const ENDPOINT_Action = 'SendUSMSMessage';

    const SUCCESS_CODE = 0;

    /**
     * Send Message.
     *
     * @param PhoneNumberInterface $to
     * @param MessageInterface     $message
     * @param Config               $config
     *
     * @return array
     *
     * @throws GatewayErrorException
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $params = $this->buildParams($to, $message, $config);

        $result = $this->get(self::ENDPOINT_URL, $params);

        if (self::SUCCESS_CODE != $result['RetCode']) {
            throw new GatewayErrorException($result['Message'], $result['RetCode'], $result);
        }

        return $result;
    }

    /**
     * Build Params.
     *
     * @param PhoneNumberInterface $to
     * @param MessageInterface     $message
     * @param Config               $config
     *
     * @return array
     */
    protected function buildParams(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $data = $message->getData($this);
        $params = [
            'Action' => self::ENDPOINT_Action,
            'SigContent' => $config->get('sig_content'),
            'TemplateId' => $message->getTemplate($this),
            'PublicKey' => $config->get('public_key'),
        ];
        $code = isset($data['code']) ? $data['code'] : '';
        if (is_array($code) && !empty($code)) {
            foreach ($code as $key => $value) {
                $params['TemplateParams.'.$key] = $value;
            }
        } else {
            if (!empty($code) || !is_null($code)) {
                $params['TemplateParams.0'] = $code;
            }
        }

        $mobiles = isset($data['mobiles']) ? $data['mobiles'] : '';
        if (!empty($mobiles) && !is_null($mobiles)) {
            if (is_array($mobiles)) {
                foreach ($mobiles as $key => $value) {
                    $params['PhoneNumbers.'.$key] = $value;
                }
            } else {
                $params['PhoneNumbers.0'] = $mobiles;
            }
        } else {
            $params['PhoneNumbers.0'] = $to->getNumber();
        }

        if (!is_null($config->get('project_id')) && !empty($config->get('project_id'))) {
            $params['ProjectId'] = $config->get('project_id');
        }

        $signature = $this->getSignature($params, $config->get('private_key'));
        $params['Signature'] = $signature;

        return $params;
    }

    /**
     * Generate Sign.
     *
     * @param array  $params
     * @param string $privateKey
     *
     * @return string
     */
    protected function getSignature($params, $privateKey)
    {
        ksort($params);

        $paramsData = '';
        foreach ($params as $key => $value) {
            $paramsData .= $key;
            $paramsData .= $value;
        }
        $paramsData .= $privateKey;

        return sha1($paramsData);
    }
}
