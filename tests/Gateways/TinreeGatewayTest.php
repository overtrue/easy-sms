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
use Overtrue\EasySms\Gateways\TinreeGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class TinreeGatewayTest extends TestCase
{
    public function test_send()
    {
        $config = [
            'accesskey' => 'mock-accesskey',
            'secret' => 'mock-secret',
            'sign' => 'mock-sign',
        ];

        $gateway = \Mockery::mock(TinreeGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'accesskey' => $config['accesskey'],
            'secret' => $config['secret'],
            'sign' => $config['sign'],
            'templateId' => '123456',
            'mobile' => '18888888888',
            'content' => '1234##5',
        ];

        $gateway->shouldReceive('post')
            ->with(TinreeGateway::ENDPOINT_URL, $params)
            ->andReturn([
                'code' => '0',
                'msg' => 'SUCCESS',
                'smUuid' => 'xxx',
            ], [
                'code' => '9006',
                'msg' => ' 用户accesskey不正确',
                'smUuid' => '',
            ])->twice();

        $message = new Message(['data' => ['1234', 5], 'template' => '123456']);
        $config = new Config($config);
        $this->assertSame(
            [
                'code' => '0',
                'msg' => 'SUCCESS',
                'smUuid' => 'xxx',
            ],
            $gateway->send(new PhoneNumber(18888888888), $message, $config)
        );

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode('9006');
        $this->expectExceptionMessage('用户accesskey不正确');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
