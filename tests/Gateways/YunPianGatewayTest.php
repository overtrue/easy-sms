<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Gateways\YunPianGateway;
use Overtrue\EasySms\Tests\TestCase;

class YunPianGatewayTest extends TestCase
{
    public function testSend()
    {
        $gateway = \Mockery::mock(YunPianGateway::class.'[post]', [[
            'api_key' => 'mock-api-key',
            'signature' => '【overtrue】',
        ]])->shouldAllowMockingProtectedMethods();

        $gateway->expects()->post('https://sms.yunpian.com/v2/sms/single_send.json', [
            'apikey' => 'mock-api-key',
            'mobile' => 18188888888,
            'text' => '【overtrue】This is a test message.',
        ])->andReturn('mock-result')->once();

        $this->assertSame('mock-result', $gateway->send(18188888888, 'This is a test message.'));
    }
}
