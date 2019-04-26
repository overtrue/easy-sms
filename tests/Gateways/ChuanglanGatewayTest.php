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
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Gateways\ChuanglanGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

/**
 * Class ChuanglanGatewayTest.
 */
class ChuanglanGatewayTest extends TestCase
{
    /**
     * 发送验证码通道短信.
     */
    public function testSendValidateCodeSMS()
    {
        $config = [
            'account' => 'mock-account',
            'password' => 'mock-password',
            'channel' => ChuanglanGateway::CHANNEL_VALIDATE_CODE,
        ];

        $gateway = \Mockery::mock(ChuanglanGateway::class.'[postJson]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('postJson')
            ->with('https://smsbj1.253.com/msg/send/json', [
                'account' => 'mock-account',
                'password' => 'mock-password',
                'phone' => 18188888888,
                'msg' => 'This is a test message.',
            ])
            ->andReturn([
                'code' => '0',
                'msgId' => '17041010383624511',
                'time' => '17041010383624511',
                'errorMsg' => '',
            ], [
                'code' => '110',
                'msgId' => '',
                'time' => '17041010383624512',
                'errorMsg' => 'Error Message',
            ])
            ->twice();

        $message = new Message(['content' => 'This is a test message.']);
        $config = new Config($config);
        $this->assertSame(
            [
                'code' => '0',
                'msgId' => '17041010383624511',
                'time' => '17041010383624511',
                'errorMsg' => '',
            ],
            $gateway->send(new PhoneNumber(18188888888), $message, $config)
        );

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(110);
        $this->expectExceptionMessage('Error Message');

        $gateway->send(new PhoneNumber(18188888888), $message, $config);
    }

    /**
     * 发送营销通道短信.
     */
    public function testSendPromotionSMS()
    {
        $config = [
            'account' => 'mock-account',
            'password' => 'mock-password',
            'channel' => ChuanglanGateway::CHANNEL_PROMOTION_CODE,
            'sign' => '【通讯云】',
            'unsubscribe' => '回TD退订',
        ];

        $gateway = \Mockery::mock(ChuanglanGateway::class.'[postJson]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('postJson')
            ->with('https://smssh1.253.com/msg/send/json', [
                'account' => 'mock-account',
                'password' => 'mock-password',
                'phone' => 18188888888,
                'msg' => '【通讯云】This is a test message.回TD退订',
            ])
            ->andReturn([
                'code' => '0',
                'msgId' => '17041010383624514',
                'time' => '17041010383624514',
                'errorMsg' => '',
            ], [
                'code' => '110',
                'msgId' => '',
                'time' => '17041010383624512',
                'errorMsg' => 'Error Message',
            ])
            ->twice();

        $message = new Message(['content' => 'This is a test message.']);
        $config = new Config($config);
        $this->assertSame(
            [
                'code' => '0',
                'msgId' => '17041010383624514',
                'time' => '17041010383624514',
                'errorMsg' => '',
            ],
            $gateway->send(new PhoneNumber(18188888888), $message, $config)
        );

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(110);
        $this->expectExceptionMessage('Error Message');

        $gateway->send(new PhoneNumber(18188888888), $message, $config);
    }

    /**
     * buildEndpoint.
     *
     * @throws \ReflectionException
     */
    public function testBuildEndpoint()
    {
        $method = new \ReflectionMethod(ChuanglanGateway::class, 'buildEndpoint');
        $method->setAccessible(true);
        $gateway = \Mockery::mock(ChuanglanGateway::class.'[request]', [[]])->shouldAllowMockingProtectedMethods();

        // 验证码通道
        $config = ['channel' => ChuanglanGateway::CHANNEL_VALIDATE_CODE];
        $config = new Config($config);
        $endpoint = 'https://smsbj1.253.com/msg/send/json';
        $this->assertSame($endpoint, $method->invoke($gateway, $config, 86));

        // 营销通道
        $config = ['channel' => ChuanglanGateway::CHANNEL_PROMOTION_CODE];
        $config = new Config($config);
        $endpoint = 'https://smssh1.253.com/msg/send/json';
        $this->assertSame($endpoint, $method->invoke($gateway, $config, 86));
    }

    /**
     * 获取通道.
     *
     * @throws \ReflectionException
     */
    public function testGetChannel()
    {
        $method = new \ReflectionMethod(ChuanglanGateway::class, 'getChannel');
        $method->setAccessible(true);
        $gateway = \Mockery::mock(ChuanglanGateway::class.'[request]', [[]])->shouldAllowMockingProtectedMethods();

        // 验证码通道
        $config = ['channel' => ChuanglanGateway::CHANNEL_VALIDATE_CODE];
        $config = new Config($config);
        $this->assertSame(ChuanglanGateway::CHANNEL_VALIDATE_CODE, $method->invoke($gateway, $config, 86));

        // 营销通道
        $config = ['channel' => ChuanglanGateway::CHANNEL_PROMOTION_CODE];
        $config = new Config($config);
        $this->assertSame(ChuanglanGateway::CHANNEL_PROMOTION_CODE, $method->invoke($gateway, $config, 86));
    }

    /**
     * 无效通道.
     *
     * @throws \ReflectionException
     */
    public function testGetChannelException()
    {
        $method = new \ReflectionMethod(ChuanglanGateway::class, 'getChannel');
        $method->setAccessible(true);
        $gateway = \Mockery::mock(ChuanglanGateway::class.'[request]', [[]])->shouldAllowMockingProtectedMethods();

        // 无效通道
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid channel for ChuanglanGateway.');
        $config = ['channel' => 'error'];
        $config = new Config($config);
        $method->invoke($gateway, $config, 86);
    }

    /**
     * 验证码通道.
     *
     * @throws \ReflectionException
     */
    public function testValidateCodeChannelWrapChannelContent()
    {
        $method = new \ReflectionMethod(ChuanglanGateway::class, 'wrapChannelContent');
        $method->setAccessible(true);
        $gateway = \Mockery::mock(ChuanglanGateway::class.'[request]', [[]])->shouldAllowMockingProtectedMethods();

        $content = '这是短信内容。';

        // 验证码通道
        $config = ['channel' => ChuanglanGateway::CHANNEL_VALIDATE_CODE];
        $config = new Config($config);
        $this->assertSame('这是短信内容。', $method->invoke($gateway, $content, $config, 86));
    }

    /**
     * 营销通道.
     *
     * @throws \ReflectionException
     */
    public function testPromotionChannelWrapChannelContent()
    {
        $method = new \ReflectionMethod(ChuanglanGateway::class, 'wrapChannelContent');
        $method->setAccessible(true);
        $gateway = \Mockery::mock(ChuanglanGateway::class.'[request]', [[]])->shouldAllowMockingProtectedMethods();

        $content = '这是短信内容。';

        // 营销通道
        $config = [
            'channel' => ChuanglanGateway::CHANNEL_PROMOTION_CODE,
            'sign' => '【通讯云】',
            'unsubscribe' => '回TD退订',
        ];
        $config = new Config($config);
        $this->assertSame('【通讯云】这是短信内容。回TD退订', $method->invoke($gateway, $content, $config, 86));
    }

    /**
     * 营销通道 -- 缺少签名.
     *
     * @throws \ReflectionException
     */
    public function testPromotionChannelWrapChannelContentWithoutSign()
    {
        $method = new \ReflectionMethod(ChuanglanGateway::class, 'wrapChannelContent');
        $method->setAccessible(true);
        $gateway = \Mockery::mock(ChuanglanGateway::class.'[request]', [[]])->shouldAllowMockingProtectedMethods();

        $content = '这是短信内容。';

        // 营销通道 -- 缺少签名
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sign for ChuanglanGateway when using promotion channel');
        $config = [
            'channel' => ChuanglanGateway::CHANNEL_PROMOTION_CODE,
            'sign' => '',
            'unsubscribe' => '回TD退订',
        ];
        $config = new Config($config);
        $method->invoke($gateway, $content, $config, 86);
    }

    /**
     * 营销通道 -- 缺少退订.
     *
     * @throws \ReflectionException
     */
    public function testPromotionChannelWrapChannelContentWithoutUnsubscribe()
    {
        $method = new \ReflectionMethod(ChuanglanGateway::class, 'wrapChannelContent');
        $method->setAccessible(true);
        $gateway = \Mockery::mock(ChuanglanGateway::class.'[request]', [[]])->shouldAllowMockingProtectedMethods();

        $content = '这是短信内容。';

        // 营销通道 -- 缺少退订
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid unsubscribe for ChuanglanGateway when using promotion channel');
        $config = [
            'channel' => ChuanglanGateway::CHANNEL_PROMOTION_CODE,
            'sign' => '【通讯云】',
            'unsubscribe' => '',
        ];
        $config = new Config($config);
        $method->invoke($gateway, $content, $config, 86);
    }
}
