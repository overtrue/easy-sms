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
use Overtrue\EasySms\Gateways\DreamNetGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class DreamNetGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'user_id' => 'mock-user-id',
            'password' => 'mock-password',
            'psz_sub_port' => '*',
            'ip' => '127.0.0.1',
            'port' => '8080',
        ];
        $gateway = \Mockery::mock(DreamNetGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('post')->with('http://127.0.0.1:8080/MWGate/wmgw.asmx/MongateSendSubmit', [
            'userId' => 'mock-user-id',
            'password' => 'mock-password',
            'pszSubPort' => '*',
            'pszMobis' => 18188888888,
            'pszMsg' => '【TIGERB】This is a test message.',
            'iMobiCount' => 1,
            'MsgId' => '8485643440204283743',
        ])->andReturn(['8485643440204283743'], ['-10001'])->times(2);

        $message = new Message([
            'content' => '【TIGERB】This is a test message.',
            'msgId' => '8485643440204283743',
        ]);
        $config = new Config($config);
        $this->assertSame(
            '8485643440204283743',
            $gateway->send(18188888888, $message, $config)
        );

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('发送失败');

        $gateway->send(18188888888, $message, $config);
    }
}
