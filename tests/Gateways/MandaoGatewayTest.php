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
use Overtrue\EasySms\Gateways\MandaoGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class MandaoGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'sn' => 'mock-sn',
            'password' => 'mock-password',
            'ext' => 1,
        ];
        $gateway = \Mockery::mock(MandaoGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'sn' => 'mock-sn',
            'mobile' => 18188888888,
            'content' => 'This is a test message.',
            'ext' => 1,
            'stime' => '',
            'rrid' => '',
            'msgfmt' => '',
            'pwd' => 'CDD24B059852DD5A6D766AF2D6568ECD',
        ];
        $gateway->shouldReceive('post')->with('http://sdk.entinfo.cn:8061/webservice.asmx/mdsmssend', \Mockery::subset($params))
            ->andReturn([192989232], [-21])->times(2);

        $message = new Message([
            'content' => 'This is a test message.',
            'data' => [
                'stime' => '',
                'rrid' => '',
                'msgfmt' => '',
            ],
        ]);
        $config = new Config($config);

        $this->assertSame([192989232], $gateway->send(new PhoneNumber(18188888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(-21);
        $this->expectExceptionMessage('Ip鉴权失败');

        $gateway->send(new PhoneNumber(18188888888), $message, $config);
    }
}
