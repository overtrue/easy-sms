<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Gateways\AliDayuGateway;
use Overtrue\EasySms\Tests\TestCase;

class AliDayuGatewayTest extends TestCase
{
    public function testSend()
    {
        $gateway = new AliDayuGateway([
            'app_key' => 'api-key',
            'app_secret' => 'api-secret',
            'sign_name' => 'api-sign-name',
            'template_code' => 'template-code',
        ]);
        $this->assertStringStartsWith('{"error_response"',$gateway->send(18888888888,'',array('code'=>'123456','time'=>'15')));
    }

}
