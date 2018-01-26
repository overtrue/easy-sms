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
use Overtrue\EasySms\Gateways\QcloudGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class QcloudGatewayTest extends TestCase
{
    public function testGetName()
    {
        $this->assertSame('tencent', (new QcloudGateway([]))->getName());
    }

    public function testSend()
    {
        $config = [
            'sdk_app_id' => 'mock-sdk-app-id',
            'app_key' => 'mock-api-key',
        ];
        $gateway = \Mockery::mock(QcloudGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $expected = [
            'tel' => [
                'nationcode' => '86',
                'mobile' => strval(18888888888),
            ],
            'type' => 0,
            'msg' => 'This is a test message.',
            'timestamp' => time(),
            'extend' => '',
            'ext' => '',
        ];

        $gateway->shouldReceive('request')
                ->andReturn([
                    'result' => 0,
                    'errmsg' => 'OK',
                    'ext' => '',
                    'sid' => 3310228982,
                    'fee' => 1,
                ], [
                    'result' => 1001,
                    'errmsg' => 'sig校验失败',
                ])->twice();

        $message = new Message(['data' => ['type' => 0], 'content' => 'This is a test message.']);

        $config = new Config($config);

        $this->assertSame([
            'result' => 0,
            'errmsg' => 'OK',
            'ext' => '',
            'sid' => 3310228982,
            'fee' => 1,
        ], $gateway->send(18888888888, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(1001);
        $this->expectExceptionMessage('sig校验失败');

        $gateway->send(18888888888, $message, $config);
    }
}
