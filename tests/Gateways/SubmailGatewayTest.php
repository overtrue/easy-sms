<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Gateways\SubmailGateway;
use Overtrue\EasySms\Tests\TestCase;

class SubmailGatewayTest extends TestCase
{
    public function testSend()
    {
        $gateway = \Mockery::mock(SubmailGateway::class.'[post]', [[
            'app_id' => 'mock-app-id',
            'app_key' => 'mock-app-key',
            'project' => 'mock-project'
        ]])->shouldAllowMockingProtectedMethods();

        $gateway->expects()->post('https://api.mysubmail.com/message/xsend.json', [
            'appid' => 'mock-app-id',
            'signature' => 'mock-app-key',
            'project' => 'mock-project',
            'to' => 18188888888,
            'vars' => json_encode(array(['code'=>'123456','time'=>'15'])),
        ])->andReturn('mock-result')->once();

        $this->assertSame('mock-result', $gateway->send(18188888888, '',array(['code'=>'123456','time'=>'15'])));
    }
}
