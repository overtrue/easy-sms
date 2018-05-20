<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Gateways\YuntongxunGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class YuntongxunGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'debug' => false,
            'is_sub_account' => false,
            'account_sid' => 'mock-account-sid',
            'account_token' => 'mock-account-token',
            'app_id' => 'mock-app-id',
        ];
        $gateway = \Mockery::mock(YuntongxunGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')->with(
            'post',
            \Mockery::on(function ($api) {
                return 0 === strpos($api, 'https://app.cloopen.com:8883/2013-12-26/Accounts/mock-account-sid/SMS/TemplateSMS?sig=');
            }),
            \Mockery::on(function ($params) {
                return $params['json'] == [
                        'to' => '18188888888',
                        'templateId' => 5589,
                        'appId' => 'mock-app-id',
                        'datas' => ['mock-data-1', 'mock-data-2'],
                    ] && $params['headers']['Accept'] == 'application/json'
                        && $params['headers']['Content-Type'] == 'application/json;charset=utf-8';
            })
        )
        ->andReturn([
            'statusCode' => YuntongxunGateway::SUCCESS_CODE,
        ], [
            'statusCode' => 100,
        ])->twice();

        $message = new Message(['data' => ['mock-data-1', 'mock-data-2'], 'template' => 5589]);
        $config = new Config($config);

        $this->assertSame([
             'statusCode' => YuntongxunGateway::SUCCESS_CODE,
         ], $gateway->send(new PhoneNumber(18188888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(100);
        $this->expectExceptionMessage('100');

        $gateway->send(new PhoneNumber(18188888888), $message, $config);
    }
}
