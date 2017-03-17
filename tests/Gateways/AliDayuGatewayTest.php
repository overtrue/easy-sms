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
        $gateway = \Mockery::mock(AliDayuGateway::class.'[post]', [[
            'app_key' => 'mock-api-key',
            'app_secret' => 'mock-api-secret',
            'sign_name' => 'mock-api-sign-name',
            'template_code' => 'mock-template-code',
        ]])->shouldAllowMockingProtectedMethods();

        $params= [
            'method' => 'alibaba.aliqin.fc.sms.num.send',
            'format' => 'json',
            'v' => '2.0',
            'sign_method' => 'md5',
            'timestamp' => date("Y-m-d H:i:s"),
            'sms_type' => 'normal',
            'sms_free_sign_name' => 'mock-api-sign-name',
            'app_key' => 'mock-api-key',
            'sms_template_code' => 'mock-template-code',
            'rec_num' => strval(18888888888),
            'sms_param' => json_encode(array('code'=>'123456','time'=>'15'))
        ];
        $params['sign']=$this->generateSign($params);
        $gateway->expects()->post('https://eco.taobao.com/router/rest', $params)->andReturn('mock-result')->once();
        $this->assertSame('mock-result', $gateway->send(18888888888,'',array('code'=>'123456','time'=>'15')));
    }

    protected function generateSign($params)
    {
        ksort($params);
        $stringToBeSigned = 'mock-api-secret';
        foreach ($params as $key => $value) {
            if(is_string($key) && "@" != substr($value, 0, 1)) {
                $stringToBeSigned .= "$key$value";
            }
        }
        $stringToBeSigned .= 'mock-api-secret';
        return strtoupper(md5($stringToBeSigned));
    }
}
