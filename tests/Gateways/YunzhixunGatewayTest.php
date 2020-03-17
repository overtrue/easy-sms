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
use Overtrue\EasySms\Gateways\YunzhixunGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class YunzhixunGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'sid' => 'mock-sid',
            'token' => 'mock-token',
            'app_id' => 'mock-app-id',
        ];

        $gateway = \Mockery::mock(YunzhixunGateway::class.'[execute]', [$config]);
        $gateway->shouldAllowMockingProtectedMethods();

        $phone = new PhoneNumber('18888888888');

        $message = new Message([
            'template' => 'mock-template-code',
            'data' => [
                'params' => '8946,3',
            ],
        ]);

        $config = new Config($config);

        $endpoint = sprintf(
            YunzhixunGateway::ENDPOINT_TEMPLATE,
            'sms',
            YunzhixunGateway::FUNCTION_SEND_SMS
        );

        $params = [
            'sid' => 'mock-sid',
            'token' => 'mock-token',
            'appid' => 'mock-app-id',
            'templateid' => 'mock-template-code',
            'uid' => '',
            'param' => '8946,3',
            'mobile' => '18888888888',
        ];

        $gateway->shouldReceive('execute')
            ->with($endpoint, $params)
            ->andReturn('mock-result');

        $this->assertSame('mock-result', $gateway->send($phone, $message, $config));
    }

    public function testSendWithBatch()
    {
        $config = [
            'sid' => 'mock-sid',
            'token' => 'mock-token',
            'app_id' => 'mock-app-id',
        ];

        $gateway = \Mockery::mock(YunzhixunGateway::class.'[execute]', [$config]);
        $gateway->shouldAllowMockingProtectedMethods();

        $phone = new PhoneNumber('18888888888');

        $message = new Message([
            'template' => 'mock-template-code',
            'data' => [
                'params' => '8946,3',
                'uid' => 'mock-user-id',
                'mobiles' => 'mock-phone-1,mock-phone-2',
            ],
        ]);

        $config = new Config($config);

        $endpoint = sprintf(
            YunzhixunGateway::ENDPOINT_TEMPLATE,
            'sms',
            YunzhixunGateway::FUNCTION_BATCH_SEND_SMS
        );

        $params = [
            'sid' => 'mock-sid',
            'token' => 'mock-token',
            'appid' => 'mock-app-id',
            'templateid' => 'mock-template-code',
            'uid' => 'mock-user-id',
            'param' => '8946,3',
            'mobile' => 'mock-phone-1,mock-phone-2',
        ];

        $gateway->shouldReceive('execute')
            ->with($endpoint, $params)
            ->andReturn('mock-result');

        $this->assertSame('mock-result', $gateway->send($phone, $message, $config));
    }

    public function testBuildEndpoint()
    {
        $config = [
            'sid' => 'mock-sid',
            'token' => 'mock-token',
            'app_id' => 'mock-app-id',
        ];

        $method = new \ReflectionMethod(YunzhixunGateway::class, 'buildEndpoint');
        $method->setAccessible(true);

        $gateway = \Mockery::mock(YunzhixunGateway::class, [$config])->shouldAllowMockingProtectedMethods();

        $this->assertSame(
            'https://open.ucpaas.com/ol/sms/sendsms',
            $method->invoke($gateway, 'sms', YunzhixunGateway::FUNCTION_SEND_SMS)
        );

        $this->assertSame(
            'https://open.ucpaas.com/ol/sms/sendsms_batch',
            $method->invoke($gateway, 'sms', YunzhixunGateway::FUNCTION_BATCH_SEND_SMS)
        );

        $this->assertSame(
            'https://open.ucpaas.com/ol/mock-resource/mock-function',
            $method->invoke($gateway, 'mock-resource', 'mock-function')
        );
    }

    public function testBuildParams()
    {
        $config = [
            'sid' => 'mock-sid',
            'token' => 'mock-token',
            'app_id' => 'mock-app-id',
        ];

        $method = new \ReflectionMethod(YunzhixunGateway::class, 'buildParams');
        $method->setAccessible(true);

        $gateway = \Mockery::mock(YunzhixunGateway::class, [$config])->shouldAllowMockingProtectedMethods();

        $phone = new PhoneNumber('18888888888');

        $message = new Message(['template' => 'mock-template-code']);

        $config = new Config($config);

        $this->assertSame([
            'sid' => 'mock-sid',
            'token' => 'mock-token',
            'appid' => 'mock-app-id',
            'templateid' => 'mock-template-code',
            'uid' => '',
            'param' => '',
            'mobile' => '18888888888',
        ], $method->invoke($gateway, $phone, $message, $config));

        $message = new Message([
            'template' => 'mock-template-code',
            'data' => [
                'params' => '8946,3',
                'uid' => 'mock-user-id',
                'mobiles' => 'mock-phone-1,mock-phone-2',
            ],
        ]);

        $this->assertSame([
            'sid' => 'mock-sid',
            'token' => 'mock-token',
            'appid' => 'mock-app-id',
            'templateid' => 'mock-template-code',
            'uid' => 'mock-user-id',
            'param' => '8946,3',
            'mobile' => 'mock-phone-1,mock-phone-2',
        ], $method->invoke($gateway, $phone, $message, $config));
    }

    public function testExecute()
    {
        $config = [
            'sid' => 'mock-sid',
            'token' => 'mock-token',
            'app_id' => 'mock-app-id',
        ];

        $method = new \ReflectionMethod(YunzhixunGateway::class, 'execute');
        $method->setAccessible(true);

        $gateway = \Mockery::mock(YunzhixunGateway::class.'[postJson]', [$config]);
        $gateway->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('postJson')
            ->with('mock-endpoint', 'mock-params')
            ->andReturn(
                null,
                [
                    'code' => 100001,
                    'mock-error-msg',
                ],
                [
                    'code' => YunzhixunGateway::SUCCESS_CODE,
                    'msg' => 'mock-success-msg',
                ]
            );

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('null');

        $method->invoke($gateway, 'mock-endpoint', 'mock-params');

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(10001);
        $this->expectExceptionMessage('mock-error-msg');

        $method->invoke($gateway, 'mock-endpoint', 'mock-params');

        $this->assertSame([
            'code' => YunzhixunGateway::SUCCESS_CODE,
            'msg' => 'mock-success-msg',
        ], $method->invoke($gateway, 'mock-endpoint', 'mock-params'));
    }
}
