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
use Overtrue\EasySms\Gateways\JuheGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class JuheGatewayTest extends TestCase
{
    public function testGetName()
    {
        $this->assertSame('juhe', (new JuheGateway([]))->getName());
    }

    public function testSend()
    {
        $config = [
            'app_key' => 'mock-key',
        ];
        $gateway = \Mockery::mock(JuheGateway::class.'[get]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'mobile' => 18188888888,
            'tpl_id' => 'mock-tpl-id',
            'tpl_value' => http_build_query(['#code#' => 1234]),
            'dtype' => 'json',
            'key' => 'mock-key',
        ];
        $gateway->shouldReceive('get')->with(JuheGateway::ENDPOINT_URL, $params)
            ->andReturn([
                'reason' => '操作成功',
                'error_code' => 0,
            ], [
                'reason' => '操作失败',
                'error_code' => 21000,
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
            'reason' => '操作成功',
            'error_code' => 0,
        ], $gateway->send(18188888888, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(21000);
        $this->expectExceptionMessage('操作失败');

        $gateway->send(18188888888, $message, $config);
    }
}
