<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) wwp66650 <wwp66650@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Gateways\YunTongXunGateway;
use Overtrue\EasySms\Tests\TestCase;

class YunTongXunGatewayTest extends TestCase
{
    public function testSend()
    {
        $gateway = \Mockery::mock(YunTongXunGateway::class . '[request]', [[
            'is_sub_account' => false,
            'account_sid' => 'mock-account-sid',
            'account_token' => 'mock-account-token',
            'app_id' => 'mock-app-id',
            'server_ip' => 'app.cloopen.com',
            'server_port' => '8883',
        ]])->shouldAllowMockingProtectedMethods();

        $hash = date('YmdHis');
        $sig = strtoupper(md5('mock-account-sid' . 'mock-account-token' . $hash));

        $gateway->expects()->request('post', 'https://app.cloopen.com:8883/2013-12-26/Accounts/mock-account-sid/SMS/TemplateSMS?sig=' . $sig, [
            'json' => [
                'to' => 18888888888,
                'templateId' => 5589,
                'appId' => 'mock-app-id',
                'datas' => ['mock-data-1', 'mock-data-2']
            ],
            'headers' => [
                "Accept" => 'application/json',
                "Content-Type" => 'application/json;charset=utf-8',
                "Authorization" => base64_encode('mock-account-sid:' . $hash),
            ],
        ])->andReturn('mock-result')->once();

        $this->assertSame('mock-result', $gateway->send(18888888888, 5589, ['mock-data-1', 'mock-data-2']));
    }
}
