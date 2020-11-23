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
use Overtrue\EasySms\Gateways\KingttoGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class KingttoGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'userid' => 'mock-id',
            'account' => 'mock-account',
            'password' => 'mock-password',
        ];

        $gateway = \Mockery::mock(KingttoGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'action' => KingttoGateway::ENDPOINT_METHOD,
            'userid' => 'mock-id',
            'account' => 'mock-account',
            'password' => 'mock-password',
            'mobile' => '18888888888',
            'content' => '【molin】This is a test message.',
        ];

        $gateway->shouldReceive('post')->with(KingttoGateway::ENDPOINT_URL, $params)
            ->andReturn([
                'returnstatus' => 'Success',
                'message' => 'ok',
                'remainpoint' => '56832',
                'taskID' => '106470408',
                'successCounts' => '1',
            ], [
                'returnstatus' => 'Faild',
                'message' => 'mock-message',
                'remainpoint' => '0',
                'taskID' => '0',
                'successCounts' => '0',
            ])->times(2);

        $this->assertSame([
            'returnstatus' => 'Success',
            'message' => 'ok',
            'remainpoint' => '56832',
            'taskID' => '106470408',
            'successCounts' => '1',
        ], $gateway->send(new PhoneNumber('18888888888'), new Message([
            'content' => '【molin】This is a test message.',
        ]), new Config($config)));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('mock-message');

        $gateway->send(new PhoneNumber('18888888888'), new Message([
            'content' => '【molin】This is a test message.',
        ]), new Config($config));
    }
}
