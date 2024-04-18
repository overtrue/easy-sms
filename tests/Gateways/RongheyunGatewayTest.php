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
use Overtrue\EasySms\Gateways\RongheyunGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class RongheyunGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'username' => 'mock-username',
            'password' => 'mock-password',
            'signature' => 'mock-signature',
        ];
        $gateway = \Mockery::mock(RongheyunGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->andReturn(
                [
                    'code' => 200,
                    'msg' => 'success',
                    'tpId' => '31874',
                    'msgId' => '161553136878837480961',
                    'invalidList' => [],
                ],
                [
                    'code' => 4025,
                    'msg' => 'template records null',
                    'tpId' => '31874',
                    'msgId' => '161553131051357039361',
                    'invalidList' => null,
                ]
            )->twice();

        $message = new Message([
            'template' => 'mock-template',
            'data' => [
                'valid_code' => 888888,
            ],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'code' => 200,
            'msg' => 'success',
            'tpId' => '31874',
            'msgId' => '161553136878837480961',
            'invalidList' => [],
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(4025);
        $this->expectExceptionMessage('template records null');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
