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
use Overtrue\EasySms\Gateways\SmsbaoGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

/**
 * Class SmsbaoGatewayTest
 * @author iwindy <203962638@qq.com>
 */
class SmsbaoGatewayTest extends TestCase
{
    public function testSendWithSMS()
    {
        $config = [
            'user' => 'mock-user',
            'password' => 'mock-password'
        ];

        $gateway = \Mockery::mock(SmsbaoGateway::class . '[get]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'u' => 'mock-user',
            'p' => md5('mock-password'),
            'm' => '18188888888',
            'c' => 'This is a test message.'
        ];

        $endpoint = sprintf(SmsbaoGateway::ENDPOINT_URL, 'sms');
        $gateway->shouldReceive('get')
            ->with($endpoint, $params)
            ->andReturn('0', '30')
            ->times(2);

        $message = new Message(['content' => 'This is a test message.']);
        $config = new Config($config);

        $this->assertSame(
            '0',
            $gateway->send(new PhoneNumber(18188888888), $message, $config)
        );

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(30);
        $gateway->send(new PhoneNumber(18188888888), $message, $config);
    }

    public function testSendWithWSMS()
    {
        $config = [
            'user' => 'mock-user',
            'password' => 'mock-password'
        ];

        $gateway = \Mockery::mock(SmsbaoGateway::class . '[get]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'u' => 'mock-user',
            'p' => md5('mock-password'),
            'm' => '+8618188888888',
            'c' => 'This is a test message.'
        ];

        $endpoint = sprintf(SmsbaoGateway::ENDPOINT_URL, 'wsms');
        $gateway->shouldReceive('get')
            ->with($endpoint, $params)
            ->andReturn('0', '30')
            ->times(2);

        $message = new Message(['content' => 'This is a test message.']);
        $config = new Config($config);

        $this->assertSame(
            '0',
            $gateway->send(new PhoneNumber(18188888888, 86), $message, $config)
        );

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(30);
        $gateway->send(new PhoneNumber(18188888888, 86), $message, $config);
    }
}
