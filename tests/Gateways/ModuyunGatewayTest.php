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
use Overtrue\EasySms\Gateways\ModuyunGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class ModuyunGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'accesskey' => 'mock-accesskey',
            'secretkey' => 'mock-secretkey',
        ];
        $gateway = \Mockery::mock(ModuyunGateway::class . '[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->andReturn(
                [
                    'result' => 0,
                    'errmsg' => 'OK',
                    'ext' => '',
                    'sid' => "mock-sid",
                    'surplus' => 4,
                    'balance' => 0,
                ],
                [
                    'result' => 1001,
                    'errmsg' => 'accesskey not exist.',
                ]
            )->twice();

        $message = new Message([
            'template' => 'mock-template',
            'data' => [
                'code' => 1234,
            ],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'result' => 0,
            'errmsg' => 'OK',
            'ext' => '',
            'sid' => "mock-sid",
            'surplus' => 4,
            'balance' => 0,
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(1001);
        $this->expectExceptionMessage('accesskey not exist.');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
