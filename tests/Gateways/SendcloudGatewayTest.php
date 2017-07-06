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
use Overtrue\EasySms\Gateways\SendcloudGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class SendcloudGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'sms_user' => 'mock-user',
            'sms_key' => 'mock-key',
        ];
        $gateway = \Mockery::mock(SendcloudGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $expected = [
            'smsUser' => 'mock-user',
            'templateId' => 'mock-tpl-id',
            'phone' => 18188888888,
            'vars' => json_encode(['%code%' => 1234]),
        ];
        $gateway->shouldReceive('post')
            ->with(sprintf(SendcloudGateway::ENDPOINT_TEMPLATE, 'send'), \Mockery::on(function ($params) use ($expected, $config) {
                $expected['timestamp'] = $params['timestamp'];
                ksort($expected);
                $signString = [];
                foreach ($expected as $key => $value) {
                    $signString[] = "{$key}={$value}";
                }
                $signString = implode('&', $signString);

                $expectedSignature = md5("{$config['sms_key']}&{$signString}&{$config['sms_key']}");

                return $params['smsUser'] == $expected['smsUser']
                    && $params['templateId'] == $expected['templateId']
                    && $params['phone'] == $expected['phone']
                    && $params['vars'] == $expected['vars']
                    && $params['timestamp'] >= time()
                    && $params['signature'] == $expectedSignature
                ;
            }))
            ->andReturn([
                'message' => '操作成功',
                'result' => true,
                'statusCode' => 200,
            ], [
                'message' => '手机号不存在',
                'result' => false,
                'statusCode' => 400,
            ])->times(2);

        $message = new Message([
                'content' => 'This is a huyi test message.',
                'template' => 'mock-tpl-id',
                'data' => [
                    'code' => 1234,
                ],
            ]);

        $config = new Config($config);

        $this->assertSame([
            'message' => '操作成功',
            'result' => true,
            'statusCode' => 200,
        ], $gateway->send(18188888888, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('手机号不存在');

        $gateway->send(18188888888, $message, $config);
    }
}
