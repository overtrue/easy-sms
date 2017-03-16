<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) Jiajian Chan <changejian@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Gateways\LuosimaoGateway;
use Overtrue\EasySms\Tests\TestCase;

class LuosimaoGatewayTest extends TestCase
{
    public function testSend()
    {
        $gateway = \Mockery::mock(LuosimaoGateway::class.'[post]', [[
            'api_key' => 'mock-api-key',
        ]])->shouldAllowMockingProtectedMethods();

        $gateway->expects()->post('https://sms-api.luosimao.com/v1/send.json', [
            'mobile' => 18188888888,
            'message' => '【overtrue】This is a test message.',
        ], [
            'Authorization' => 'Basic ' . base64_encode('api:key-mock-api-key')
        ])->andReturn('mock-result')->once();

        $this->assertSame('mock-result', $gateway->send(18188888888, '【overtrue】This is a test message.'));
    }
}
