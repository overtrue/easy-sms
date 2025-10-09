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
    public function test_send()
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
                return strpos($api, 'https://app.cloopen.com:8883/2013-12-26/Accounts/mock-account-sid/SMS/TemplateSMS?sig=') === 0;
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

    // 国际短信
    public function test_send_intl()
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
                return strpos($api, 'https://app.cloopen.com:8883/v2/account/mock-account-sid/international/send?sig=') === 0;
            }),
            \Mockery::on(function ($params) {
                return $params['json'] == [
                    'appId' => 'mock-app-id',
                    'mobile' => '006018188888888',
                    'content' => '容联云国际短信测试',
                ] && $params['headers']['Accept'] == 'application/json'
                && $params['headers']['Content-Type'] == 'application/json;charset=utf-8';
            })
        )->andReturn([
            'statusCode' => YuntongxunGateway::SUCCESS_CODE,
        ], [
            'statusCode' => 100,
        ]);

        $message = new Message(['content' => '容联云国际短信测试']);
        $config = new Config($config);

        $this->assertSame([
            'statusCode' => YuntongxunGateway::SUCCESS_CODE,
        ], $gateway->send(new PhoneNumber(18188888888, '+60'), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(100);
        $this->expectExceptionMessage('100');

        $gateway->send(new PhoneNumber(18188888888, '+60'), $message, $config);
    }
}
