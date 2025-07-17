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
use Overtrue\EasySms\Gateways\WeiqucloudGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class WeiqucloudGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'userId' => 'mock-user-id',
            'account' => 'mock-account',
            'password' => 'mock-password',
        ];
        $gateway = \Mockery::mock(WeiqucloudGateway::class.'[postJson]', [$config])->shouldAllowMockingProtectedMethods();

        $expected = [
            'userId' => 'mock-user-id',
            'account' => 'mock-account',
            'password' => 'mock-password',
            'mobile' => '18188888888',
            'content' => 'This is a test message.',
            'sendTime' => '',
            'action' => 'sendhy',
        ];

        $gateway->shouldReceive('postJson')
            ->with(WeiqucloudGateway::ENDPOINT_URL, $expected)
            ->andReturn(
                1, // 成功返回正数
                -1 // 失败返回负数
            )
            ->twice();

        $message = new Message(['content' => 'This is a test message.']);
        $config = new Config($config);

        // 测试成功发送
        $this->assertSame(1, $gateway->send(new PhoneNumber(18188888888), $message, $config));

        // 测试发送失败抛出异常
        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(-1);
        $this->expectExceptionMessage('短信发送失败');

        $gateway->send(new PhoneNumber(18188888888), $message, $config);
    }
} 