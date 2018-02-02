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
use Overtrue\EasySms\Gateways\AvatardataGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class AvatardataGatewayTest extends TestCase
{
    public function testGetName()
    {
        $this->assertSame('avatardata', (new AvatardataGateway([]))->getName());
    }

    public function testSend()
    {
        $config = [
            'app_key' => 'mock-key',
        ];
        $gateway = \Mockery::mock(AvatardataGateway::class.'[get]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'mobile' => 18188888888,
            'templateId' => 'mock-tpl-id',
            'param' => implode(',', ['1234']),
            'dtype' => AvatardataGateway::ENDPOINT_FORMAT,
            'key' => 'mock-key',
        ];
        $gateway->shouldReceive('get')->with(AvatardataGateway::ENDPOINT_URL, $params)
            ->andReturn([
                'reason' => 'Success',
                'error_code' => 0,
            ], [
                'reason' => '错误的请求KEY',
                'error_code' => 10001,
            ])->times(2);

        $message = new Message([
            'content' => 'This is a test message.',
            'template' => 'mock-tpl-id',
            'data' => [
                '1234'
            ],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'reason' => 'Success',
            'error_code' => 0,
        ], $gateway->send(18188888888, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(10001);
        $this->expectExceptionMessage('错误的请求KEY');

        $gateway->send(18188888888, $message, $config);
    }
}
