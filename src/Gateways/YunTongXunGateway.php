<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\HasHttpRequest;

class YunTongXunGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'https://%s:%s/%s/%s/%s/%s/%s?sig=%s';
    const SDK_VERSION = '2013-12-26';

    /**
     * Send a short message.
     *
     * @param string|int $to
     * @param string     $template_id
     * @param array      $data
     *
     * @return mixed
     */
    public function send($to, $template_id, array $data = [])
    {
        $datetime = date('YmdHis');

        $endpoint = $this->buildEndpoint('SMS', 'TemplateSMS', $datetime);

        return $this->request('post', $endpoint, [
            'json' => [
                'to' => $to,
                'templateId' => $template_id,
                'appId' => $this->config->get('app_id'),
                'datas' => $data
            ],
            'headers' => [
                "Accept" => 'application/json',
                "Content-Type" => 'application/json;charset=utf-8',
                "Authorization" => base64_encode($this->config->get('account_sid') . ":" . $datetime),
            ],
        ]);
    }

    /**
     * Build endpoint url.
     *
     * @param string $type
     * @param string $resource
     * @param string $datetime
     *
     * @return string
     */
    protected function buildEndpoint($type, $resource, $datetime)
    {
        $account_type = $this->config->get('is_sub_account') ? 'SubAccounts' : 'Accounts';

        $sig = strtoupper(md5($this->config->get('account_sid') . $this->config->get('account_token') . $datetime));

        return sprintf(self::ENDPOINT_TEMPLATE, $this->config->get('server_ip'), $this->config->get('server_port'), self::SDK_VERSION, $account_type, $this->config->get('account_sid'), $type, $resource, $sig);
    }
}
