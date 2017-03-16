<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) wwp66650 <wwp66650@gmail.com>
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

    protected $server_ip;
    protected $server_port;
    protected $account_sid;

    /**
     * YunTongXunGateway constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->server_ip = $config['server_ip'];
        $this->server_port = $config['server_port'];
        $this->account_sid = $config['account_sid'];
        parent::__construct($config);
    }

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
                "Authorization" => base64_encode($this->account_sid . ":" . $datetime),
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
        // 大写的sig参数
        $sig = strtoupper(md5($this->account_sid . $this->config->get('account_token') . $datetime));

        return sprintf(self::ENDPOINT_TEMPLATE, $this->server_ip, $this->server_port, self::SDK_VERSION, $account_type, $this->account_sid, $type, $resource, $sig);
    }
}
