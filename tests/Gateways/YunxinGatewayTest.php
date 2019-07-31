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
use Overtrue\EasySms\Gateways\YunxinGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class YunxinGatewayTest extends TestCase
{
    public function testSendWithSendCode()
    {
        $config = [
            'app_key' => 'mock-app-key',
            'app_secret' => 'mock-app-secret',
        ];

        $gateway = \Mockery::mock(YunxinGateway::class.'[post,buildHeaders,buildSendCodeParams]', [$config]);
        $gateway->shouldAllowMockingProtectedMethods();

        $phone = new PhoneNumber('18888888888');

        $message = new Message([
            'template' => 'mock-template-code',
            'data' => ['code' => '123456'],
        ]);

        $config = new Config($config);

        $gateway->shouldReceive('buildHeaders')
            ->with($config)
            ->andReturn('mock-headers');

        $gateway->shouldReceive('buildSendCodeParams')
            ->with($phone, $message, $config)
            ->andReturn('mock-params');

        $gateway->shouldReceive('post')
            ->with('https://api.netease.im/sms/sendcode.action', 'mock-params', 'mock-headers')
            ->andReturn([
                'code' => 200,
                'msg' => 5,
                'obj' => 6379,
            ], [
                'code' => 414,
                'msg' => 'checksum',
            ])
            ->twice();

        $this->assertSame([
            'code' => 200,
            'msg' => 5,
            'obj' => 6379,
        ], $gateway->send($phone, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(414);
        $this->expectExceptionMessage('checksum');

        $gateway->send($phone, $message, $config);
    }

    public function testSendWithVerifyCode()
    {
        $config = [
            'app_key' => 'mock-app-key',
            'app_secret' => 'mock-app-secret',
        ];

        $gateway = \Mockery::mock(YunxinGateway::class.'[post,buildHeaders,buildVerifyCodeParams]', [$config]);
        $gateway->shouldAllowMockingProtectedMethods();

        $phone = new PhoneNumber('18888888888');

        $message = new Message([
            'template' => 'mock-template-code',
            'data' => [
                'code' => '123456',
                'action' => 'verifyCode',
            ],
        ]);

        $config = new Config($config);

        $gateway->shouldReceive('buildHeaders')
            ->with($config)
            ->andReturn('mock-headers');

        $gateway->shouldReceive('buildVerifyCodeParams')
            ->with($phone, $message)
            ->andReturn('mock-params');

        $gateway->shouldReceive('post')
            ->with('https://api.netease.im/sms/verifycode.action', 'mock-params', 'mock-headers')
            ->andReturn([
                'code' => 200,
            ], [
                'code' => 414,
                'msg' => 'checksum',
            ])
            ->twice();

        $this->assertSame([
            'code' => 200,
        ], $gateway->send($phone, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(414);
        $this->expectExceptionMessage('checksum');

        $gateway->send($phone, $message, $config);
    }

    public function testBuildEndpoint()
    {
        $config = [
            'app_key' => 'mock-app-key',
            'app_secret' => 'mock-app-secret',
        ];

        $method = new \ReflectionMethod(YunxinGateway::class, 'buildEndpoint');
        $method->setAccessible(true);

        $gateway = \Mockery::mock(YunxinGateway::class, [$config])->shouldAllowMockingProtectedMethods();

        $this->assertSame(
            'https://api.netease.im/sms/sendcode.action',
            $method->invoke($gateway, 'sms', 'sendCode')
        );

        $this->assertSame(
            'https://api.netease.im/mock-resource/mock-function.action',
            $method->invoke($gateway, 'mock-resource', 'mock-function')
        );
    }

    public function testBuildHeaders()
    {
        $config = [
            'app_key' => 'mock-app-key',
            'app_secret' => 'mock-app-secret',
        ];

        $method = new \ReflectionMethod(YunxinGateway::class, 'buildHeaders');
        $method->setAccessible(true);

        $gateway = \Mockery::mock(YunxinGateway::class, [$config])->shouldAllowMockingProtectedMethods();

        $headers = $method->invoke($gateway, new Config($config));

        $this->assertSame($config['app_key'], $headers['AppKey']);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertSame('application/x-www-form-urlencoded;charset=utf-8', $headers['Content-Type']);

        $checkSum = sha1("{$config['app_secret']}{$headers['Nonce']}{$headers['CurTime']}");
        $this->assertSame($checkSum, $headers['CheckSum']);
    }

    public function testBuildSendCodeParams()
    {
        $config = [
            'app_key' => 'mock-app-key',
            'app_secret' => 'mock-app-secret',
            'code_length' => 5,
            'need_up' => true,
        ];

        $method = new \ReflectionMethod(YunxinGateway::class, 'buildSendCodeParams');
        $method->setAccessible(true);

        $gateway = \Mockery::mock(YunxinGateway::class, [$config])->shouldAllowMockingProtectedMethods();

        $phone = new PhoneNumber('18888888888');

        $message = new Message([
            'template' => 'mock-template-code',
            'data' => [
                'code' => '123456',
                'device_id' => 'mock-device-id',
            ],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'mobile' => '18888888888',
            'authCode' => '123456',
            'deviceId' => 'mock-device-id',
            'templateid' => 'mock-template-code',
            'codeLen' => 5,
            'needUp' => true,
        ], $method->invoke($gateway, $phone, $message, $config));
    }

    public function testBuildVerifyCodeParams()
    {
        $config = [
            'app_key' => 'mock-app-key',
            'app_secret' => 'mock-app-secret',
        ];

        $method = new \ReflectionMethod(YunxinGateway::class, 'buildVerifyCodeParams');
        $method->setAccessible(true);

        $gateway = \Mockery::mock(YunxinGateway::class, [$config])->shouldAllowMockingProtectedMethods();

        $phone = new PhoneNumber('18888888888');

        $message = new Message([
            'data' => [
                'code' => '123456',
                'action' => 'verfiyCode',
            ],
        ]);

        $this->assertSame([
            'mobile' => '18888888888',
            'code' => '123456',
        ], $method->invoke($gateway, $phone, $message));

        $message = new Message([
            'data' => [
                'action' => 'verfiyCode',
            ],
        ]);

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('"code" cannot be empty');

        $method->invoke($gateway, $phone, $message);
    }
}
