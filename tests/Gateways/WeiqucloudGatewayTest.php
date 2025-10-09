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
use Overtrue\EasySms\Gateways\WeiqucloudGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class WeiqucloudGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'userId' => 'mock-user-id',
            'account' => 'mock-account',
            'password' => 'mock-password',
        ];
        $gateway = \Mockery::mock(WeiqucloudGateway::class.'[postJson]', [$config])->shouldAllowMockingProtectedMethods();

        $expected = [
            'userId' => 'mock-user-id',
            'account' => 'mock-account',
            'password' => 'mock-password',
            'mobile' => '18188888888',
            'content' => 'This is a test message.',
            'sendTime' => '',
            'action' => 'sendhy',
        ];

        // 成功响应的数据结构
        $successResponse = [
            'code' => 200,
            'data' => [
                'status' => 'Success',
                'taskID' => 'mock-task-id',
                'remainPoint' => 100,
                'message' => '发送成功',
            ],
        ];

        // 失败响应的数据结构
        $failureResponse = [
            'code' => 500,
            'data' => [
                'status' => 'Failed',
                'message' => '账户余额不足',
                'remainPoint' => 0,
                'taskID' => 'mock-task-id-failed',
            ],
        ];

        $gateway->shouldReceive('postJson')
            ->with(WeiqucloudGateway::ENDPOINT_URL, $expected)
            ->andReturn($successResponse, $failureResponse)
            ->twice();

        $message = new Message(['content' => 'This is a test message.']);
        $config = new Config($config);

        // 测试成功发送
        $result = $gateway->send(new PhoneNumber(18188888888), $message, $config);
        $this->assertSame($successResponse, $result);

        // 测试发送失败抛出异常
        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('短信发送失败: 账户余额不足, remainPoint: 0, taskID:mock-task-id-failed');

        $gateway->send(new PhoneNumber(18188888888), $message, $config);
    }
}
