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
use Overtrue\EasySms\Gateways\MaapGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class MaapGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'cpcode' => 'mock-cpcode',
            'key' => 'mock-key',
            'excode' => 'mock-excode',
        ];

        $gateway = \Mockery::mock(MaapGateway::class.'[postJson]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'cpcode' => 'mock-cpcode',
            'msg' => '1234',
            'mobiles' => '18888888888',
            'excode' => 'mock-excode',
            'templetid' => '123456',
        ];
        $params['sign'] = md5($params['cpcode'].$params['msg'].$params['mobiles'].$params['excode'].$params['templetid'].$config['key']);

        $gateway->shouldReceive('postJson')
            ->with(MaapGateway::ENDPOINT_URL, $params)
            ->andReturn([
                'resultcode' => 0,
                'resultmsg' => '成功',
                'taskid' => 'C20511170688217',
            ], [
                'resultcode' => 301,
                'resultmsg' => 'Error Message',
                'taskid' => '',
            ])->twice();

        $message = new Message(['data' => ['1234'], 'template' => '123456']);
        $config = new Config($config);
        $this->assertSame(
            [
                'resultcode' => 0,
                'resultmsg' => '成功',
                'taskid' => 'C20511170688217',
            ],
            $gateway->send(new PhoneNumber(18888888888), $message, $config)
        );

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(301);
        $this->expectExceptionMessage('Error Message');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
