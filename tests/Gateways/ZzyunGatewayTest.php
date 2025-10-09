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
use Overtrue\EasySms\Gateways\ZzyunGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class ZzyunGatewayTest extends TestCase
{
    public function test_send()
    {
        $config = [
            'user_id' => 'mock-user_id',
            'secret' => 'mock-secret',
            'sign_name' => 'mock-sign_name',
        ];
        $gateway = \Mockery::mock(ZzyunGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->andReturn(
                [
                    'Code' => 'Success',
                    'Data' => [
                        'fee_count' => 1,
                        'send_count' => 1,
                        'biz_id' => '20210317143532-gtqlupiamu',
                    ],
                    'Message' => '',
                ],
                [
                    'Code' => 'fail',
                    'Data' => [],
                    'Message' => '参数错误',
                ]
            )->twice();

        $message = new Message([
            'template' => 'mock-template',
            'data' => [
                'code' => 888888,
            ],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'Code' => 'Success',
            'Data' => [
                'fee_count' => 1,
                'send_count' => 1,
                'biz_id' => '20210317143532-gtqlupiamu',
            ],
            'Message' => '',
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        // $this->expectExceptionCode('fail');
        $this->expectExceptionMessage('参数错误');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
