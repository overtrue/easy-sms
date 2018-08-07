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

use Overtrue\EasySms\Gateways\MwGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;


class MwGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'userId' => 'userId',
            'password' => 'password',
            'pszSubPort' => 'pszSubPort'
        ];
        $gateway = \Mockery::mock(MwGateway::class . '[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->andReturn(
                [
                    'code' => 1,
                    'msg' => 'success'
                ]);
        $message = new Message(['content' => 'This is a test messageoo']);
        $config = new Config($config);
        $this->assertSame([
            'code' => 1,
            'msg' => 'success',
        ], $gateway->send(new PhoneNumber(18188888888), $message, $config));
    }
}
