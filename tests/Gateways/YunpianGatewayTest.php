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
use Overtrue\EasySms\Gateways\YunpianGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class YunpianGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'api_key' => 'mock-api-key',
        ];
        $gateway = \Mockery::mock(YunpianGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('post')->with('https://sms.yunpian.com/v2/sms/single_send.json', [
            'apikey' => 'mock-api-key',
            'mobile' => 18188888888,
            'text' => '【overtrue】This is a test message.',
        ])->andReturn([
            'code' => 0,
            'msg' => '发送成功',
            'count' => 1, //成功发送的短信计费条数
            'fee' => 0.05,    //扣费条数，70个字一条，超出70个字时按每67字一条计
            'unit' => 'RMB',  // 计费单位
            'mobile' => '13200000000', // 发送手机号
            'sid' => 3310228982,   // 短信ID
        ], [
            'code' => 100,
            'msg' => '发送失败',
        ])->times(2);

        $message = new Message(['content' => '【overtrue】This is a test message.']);
        $config = new Config($config);
        $this->assertSame([
            'code' => 0,
            'msg' => '发送成功',
            'count' => 1, //成功发送的短信计费条数
            'fee' => 0.05,    //扣费条数，70个字一条，超出70个字时按每67字一条计
            'unit' => 'RMB',  // 计费单位
            'mobile' => '13200000000', // 发送手机号
            'sid' => 3310228982,   // 短信ID
        ], $gateway->send(18188888888, $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(100);
        $this->expectExceptionMessage('发送失败');

        $gateway->send(18188888888, $message, $config);
    }
}
