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
use Overtrue\EasySms\Gateways\YidongmasblackGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class YidongmasblackGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'ecName' => 'mock-ec-name',
            'secretKey' => 'mock-secret-key',
            'apId' => 'mock-ap-id',
            'sign' => 'mock-sign',
            'addSerial' => 'mock-add-serial',
        ];
        $gateway = \Mockery::mock(YidongmasblackGateway::class . '[postJson]', [$config])->shouldAllowMockingProtectedMethods();

        $expected = [
            'ecName' => "mock-ec-name",
            'apId' => "mock-ap-id",
            'sign' => "mock-sign",
            'addSerial' => "mock-add-serial",
            'mobiles' => 18888888888,
            'content' => "123456",
            'mac' => "316769171b5b29b13e1fa0a5250ff5e2",
        ];
        $gateway->shouldReceive('postJson')
            ->with(YidongmasblackGateway::ENDPOINT_URL, \Mockery::on(function ($params) use ($expected) {
                $params = json_decode(base64_decode($params), true);

                return $params == $expected;
            }))
            ->andReturn([
                'success' => 'true',
                'rspcod' => '1234',
            ], [
                'success' => 'mock-err-msg',
                'rspcod' => '1234',
            ])
            ->twice();

        $message = new Message([
            'content' => '123456',
        ]);

        $config = new Config($config);

        $this->assertSame([
            'success' => 'true',
            'rspcod' => '1234',
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(1234);
        $this->expectExceptionMessage('mock-err-msg');
        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
