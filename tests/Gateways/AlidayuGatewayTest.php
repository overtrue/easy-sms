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
use Overtrue\EasySms\Gateways\AlidayuGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class AlidayuGatewayTest extends TestCase
{
    public function testGetName()
    {
        $gateway = $this->getMockBuilder(AlidayuGateway::class)
            ->disableOriginalConstructor()
            ->getMock();
        $gateway->method('getName')
            ->willReturn('alidayu');

        $this->assertSame('alidayu', $gateway->getName());
    }

    public function testSend()
    {
        $config = [
            'app_key' => 'mock-api-key',
            'app_secret' => 'mock-api-secret',
            'sign_name' => 'mock-api-sign-name',
            'template_code' => 'mock-template-code',
        ];
        $gateway = \Mockery::mock(AlidayuGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $expected = [
            'method' => 'alibaba.aliqin.fc.sms.num.send',
            'format' => 'json',
            'v' => '2.0',
            'sign_method' => 'md5',
            'sms_type' => 'normal',
            'sms_free_sign_name' => 'mock-api-sign-name',
            'app_key' => 'mock-api-key',
            'sms_template_code' => 'mock-template-code',
            'rec_num' => strval(18888888888),
            'sms_param' => json_encode(['code' => '123456', 'time' => '15']),
        ];
        $gateway->shouldReceive('post')
            ->with('https://eco.taobao.com/router/rest', \Mockery::on(function ($params) use ($expected) {
                if (empty($params['timestamp']) || empty($params['sign'])) {
                    return false;
                }

                unset($params['timestamp'], $params['sign']);

                ksort($params);
                ksort($expected);

                return $params == $expected;
            }))
            ->andReturn([
                'success_response' => 'mock-result',
            ], [
                'error_response' => ['sub_msg' => 'mock-msg', 'code' => 100],
            ])
            ->twice();

        $message = new Message([
            'template' => 'mock-template-code',
            'data' => ['code' => '123456', 'time' => '15'],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'success_response' => 'mock-result',
        ], $gateway->send(18888888888, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(100);
        $this->expectExceptionMessage('mock-msg');

        $gateway->send(18888888888, $message, $config);
    }
}
