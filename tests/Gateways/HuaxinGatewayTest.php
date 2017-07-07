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
use Overtrue\EasySms\Gateways\HuaxinGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class HuaxinGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'user_id' => 'mock-user-id',
            'password' => 'mock-password',
            'account' => 'mock-account',
            'ip' => '127.0.0.1',
            'ext_no' => '',
        ];
        $gateway = \Mockery::mock(HuaxinGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('post')->with('http://127.0.0.1/smsJson.aspx', [
            'userid' => 'mock-user-id',
            'password' => 'mock-password',
            'account' => 'mock-account',
            'mobile' => 18188888888,
            'content' => '【TIGERB】This is a test message.',
            'sendTime' => '',
            'action' => 'send',
            'extno' => '',
        ])->andReturn([
            'returnstatus' => 'Success',
            'message' => '操作成功',
            'remainpoint' => '100',
            'taskID' => '1504080852350206',
            'successCounts' => '1',
        ], [
            'returnstatus' => 'Faild',
            'message' => '操作失败',
            'remainpoint' => '0',
            'taskID' => '0',
            'successCounts' => '0',
        ])->times(2);

        $message = new Message(['content' => '【TIGERB】This is a test message.']);
        $config = new Config($config);
        $this->assertSame(
            [
                'returnstatus' => 'Success',
                'message' => '操作成功',
                'remainpoint' => '100',
                'taskID' => '1504080852350206',
                'successCounts' => '1',
            ],
            $gateway->send(18188888888, $message, $config)
        );

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('操作失败');

        $gateway->send(18188888888, $message, $config);
    }
}
