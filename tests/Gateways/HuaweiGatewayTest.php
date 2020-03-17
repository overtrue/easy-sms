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
use Overtrue\EasySms\Gateways\HuaweiGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class HuaweiGatewayTest extends TestCase
{
    /**
     * 测试 发送华为短信
     */
    public function testSend()
    {
        $config = [
            'endpoint' => 'mock-endpoint',
            'app_key' => 'mock-app-key',
            'app_secret' => 'mock-app-secret',
            'from' => [
                'default' => 'mock-default-from',
            ],
            'callback' => 'mock-callback',
        ];

        $expectedParams = [
            'from' => 'mock-default-from',
            'to' => '13800138000',
            'templateId' => 'mock-tpl-id',
            'templateParas' => '["mock-data-1","mock-data-2"]',
            'statusCallback' => 'mock-callback',
        ];

        $gateway = \Mockery::mock(HuaweiGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->with(
                'post',
                \Mockery::on(function ($endpoint) use ($config) {
                    return $config['endpoint'].'/sms/batchSendSms/v1' === $endpoint;
                }),
                \Mockery::on(function ($params) use ($expectedParams) {
                    ksort($params['form_params']);
                    ksort($expectedParams);

                    return $params['form_params'] == $expectedParams;
                })
            )->andReturn(
                ['code' => HuaweiGateway::SUCCESS_CODE, 'description' => 'Success', 'result' => 'mock-result'],
                ['code' => 'E200037', 'description' => 'The SMS fails to be sent. For details, see status']
            )->twice();

        $message = new Message([
            'template' => 'mock-tpl-id',
            'data' => ['mock-data-1', 'mock-data-2'],
        ]);

        $config = new Config($config);
        $phoneNum = new PhoneNumber(13800138000);

        $expectedSuccessResult = ['code' => HuaweiGateway::SUCCESS_CODE, 'description' => 'Success', 'result' => 'mock-result'];

        $this->assertSame($expectedSuccessResult, $gateway->send($phoneNum, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(200037);
        $this->expectExceptionMessage('The SMS fails to be sent. For details, see status');

        $gateway->send($phoneNum, $message, $config);
    }

    /**
     * 测试 自定义签名通道.
     */
    public function testMultiFrom()
    {
        $config = [
            'endpoint' => 'mock-endpoint',
            'app_key' => 'mock-app-key',
            'app_secret' => 'mock-app-secret',
            'from' => [
                'default' => 'mock-default-from',
                'custom' => 'mock-custom-from', // 配置自定义签名通道
            ],
            'callback' => 'mock-callback',
        ];

        $expectedParams = [
            'from' => 'mock-custom-from',
            'to' => '13800138000',
            'templateId' => 'mock-tpl-id',
            'templateParas' => '["mock-data-1","mock-data-2"]',
            'statusCallback' => 'mock-callback',
        ];

        $gateway = \Mockery::mock(HuaweiGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->with(
                'post',
                \Mockery::on(function ($endpoint) use ($config) {
                    return $config['endpoint'].'/sms/batchSendSms/v1' === $endpoint;
                }),
                \Mockery::on(function ($params) use ($expectedParams) {
                    ksort($params['form_params']);
                    ksort($expectedParams);

                    return $params['form_params'] == $expectedParams;
                })
            )->andReturn(
                ['code' => HuaweiGateway::SUCCESS_CODE, 'description' => 'Success', 'result' => 'mock-result'],
                ['code' => 'E200037', 'description' => 'The SMS fails to be sent. For details, see status']
            )->twice();

        $message = new Message([
            'template' => 'mock-tpl-id',
            'data' => [
                'mock-data-1',
                'mock-data-2',
                'from' => 'custom', // 设置自定义签名通道
            ],
        ]);

        $config = new Config($config);
        $phoneNum = new PhoneNumber(13800138000);

        $expectedSuccessResult = ['code' => HuaweiGateway::SUCCESS_CODE, 'description' => 'Success', 'result' => 'mock-result'];

        $this->assertSame($expectedSuccessResult, $gateway->send($phoneNum, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(200037);
        $this->expectExceptionMessage('The SMS fails to be sent. For details, see status');

        $gateway->send($phoneNum, $message, $config);
    }

    /**
     * 测试 endpoint.
     *
     * @throws \ReflectionException
     */
    public function testGetEndpoint()
    {
        $method = new \ReflectionMethod(HuaweiGateway::class, 'getEndpoint');
        $method->setAccessible(true);

        $gateway = \Mockery::mock(HuaweiGateway::class.'[request]', [[]])->shouldAllowMockingProtectedMethods();

        $defaultEndpoint = 'https://api.rtc.huaweicloud.com:10443/sms/batchSendSms/v1';
        $this->assertSame($defaultEndpoint, $method->invoke($gateway, new Config()));

        $config = new Config([
            'endpoint' => 'mock-endpoint',
        ]);
        $endpoint = 'mock-endpoint/sms/batchSendSms/v1';
        $this->assertSame($endpoint, $method->invoke($gateway, $config));
    }
}
