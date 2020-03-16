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
use Overtrue\EasySms\Gateways\UcloudGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

/**
 * Class UcloudGatewayTest.
 */
class UcloudGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'private_key' => '', //私钥
            'public_key' => '', //公钥
            'sig_content' => '', //签名
            'project_id' => '', //默认不填，子账号才需要填
        ];

        $gateway = \Mockery::mock(UcloudGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')->with(
            'get',
            \Mockery::on(function ($api) {
                return 0 === strpos($api, UcloudGateway::ENDPOINT_URL);
            }),
            \Mockery::on(function ($params) {
                return true;
            })
        )
            ->andReturn([
                'RetCode' => UcloudGateway::SUCCESS_CODE,
            ], [
                'RetCode' => 170,
                'Message' => 'Missing signature',
            ])->times(2);

        $message = new Message([
            'template' => '',
            'data' => [
                'code' => '', // 如果是多个参数可以用数组
                'mobiles' => '', //同时发送多个手机也可以用数组来,[1111111,11111]
            ],
        ]);
        $config = new Config($config);

        $this->assertSame([
            'RetCode' => UcloudGateway::SUCCESS_CODE,
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(170);
        $this->expectExceptionMessage('Missing signature');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
