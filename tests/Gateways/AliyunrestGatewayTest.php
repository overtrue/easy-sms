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
use Overtrue\EasySms\Gateways\AliyunrestGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

/**
 * Class AliyunrestGatewayTest.
 */
class AliyunrestGatewayTest extends TestCase
{
    public function testSend()
    {
        $urlParams = [
            'app_key' => 'mock-app-key',
            'v' => '2.0',
            'format' => 'json',
            'sign_method' => 'md5',
            'method' => 'alibaba.aliqin.fc.sms.num.send',
            'timestamp' => date('Y-m-d H:i:s'),
            'partner_id' => 'EasySms',
        ];
        $config = [
            'app_key' => 'mock-app-key',
            'app_secret_key' => 'mock-app-secret',
            'sign_name' => 'mock-app-sign-name',
            'template_code' => 'mock-template-code',
        ];
        $expected = [
            'extend' => '',
            'sms_type' => 'normal',
            'sms_free_sign_name' => 'mock-app-sign-name',
            'sms_param' => json_encode(['code' => '123456']),
            'rec_num' => strval(new PhoneNumber(18888888888)),
            'sms_template_code' => 'mock-template-code',
        ];
        $gateway = \Mockery::mock(AliyunrestGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();
        $gateway->shouldReceive('post')->with(\Mockery::on(function ($url) use ($urlParams) {
            $url = implode('&', array_filter(explode('&', $url), function ($s) {
                return 'sign=' != substr($s, 0, 5);
            }));

            return $url == 'http://gw.api.taobao.com/router/rest?'.http_build_query($urlParams);
        }), \Mockery::on(function ($params) use ($expected) {
            return $params == $expected;
        }))->andReturn([
            'alibaba_aliqin_fc_sms_num_send_response' => [
                'result' => [
                    'err_code' => '0', 'msg' => 'mock-result', 'success' => true,
                ],
            ], ], [
                'error_response' => [
                    'code' => 15,
                    'msg' => 'mock-err-msg',
                ], ])->twice();

        $message = new Message([
            'template' => 'mock-template-code',
            'data' => ['code' => '123456'],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'alibaba_aliqin_fc_sms_num_send_response' => [
                'result' => [
                    'err_code' => '0', 'msg' => 'mock-result', 'success' => true,
                ],
            ], ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(15);
        $this->expectExceptionMessage('mock-err-msg');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
