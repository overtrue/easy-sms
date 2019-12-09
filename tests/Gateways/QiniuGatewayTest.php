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
use Overtrue\EasySms\Gateways\QiniuGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class QiniuGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'secret_key' => 'mock-secrey-key',
            'access_key' => 'mock-access-key',
        ];
        $gateway = \Mockery::mock(QiniuGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->andReturn([
                'message_id' => '21321974632178',
            ], [
                'error' => 'BadToken',
                'message' => 'Your authorization token is invalid',
                'request_id' => 'VEc9f6W1guxye94V',
            ])->twice();

        $message = new Message([
            'template' => 'mock-tpl-id',
            'data' => [
                'code' => 1234,
            ],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'message_id' => '21321974632178',
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionMessage('Your authorization token is invalid');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
