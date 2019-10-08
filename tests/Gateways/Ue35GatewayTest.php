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
use Overtrue\EasySms\Gateways\Ue35Gateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class Ue35GatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'debug' => false,
            'is_sub_account' => false,
            'username' => 'mock-app-id',
            'userpwd' => '',
        ];
        $gateway = \Mockery::mock(Ue35Gateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')->with(
            'post',
            \Mockery::on(function ($api) {
                return 0 === strpos($api, Ue35Gateway::getEndpointUri());
            }),
            \Mockery::on(function ($params) {
                return $params['json'] == [
                    'username' => 'mock-app-id',
                    'userpwd' => '',
                    'mobiles' => '18188888888',
                    'content' => 'content',
                ];
            })
        )
        ->andReturn([
            'errcode' => Ue35Gateway::SUCCESS_CODE,
        ], [
            'errcode' => 100,
            'message' => 'error',
        ])->twice();

        $message = new Message(['content' => 'content']);
        $config = new Config($config);

        $this->assertSame([
             'errcode' => Ue35Gateway::SUCCESS_CODE,
        ], $gateway->send(new PhoneNumber(18188888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(100);
        $this->expectExceptionMessage('error');

        $gateway->send(new PhoneNumber(18188888888), $message, $config);
    }
}
