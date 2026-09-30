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
use Overtrue\EasySms\Gateways\UcloudGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

/**
 * Class UcloudGatewayTest.
 */
class UcloudGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'private_key' => '', // 私钥
            'public_key' => '', // 公钥
            'sig_content' => '', // 签名
            'project_id' => '', // 默认不填，子账号才需要填
        ];

        $gateway = \Mockery::mock(UcloudGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')->with(
            'get',
            \Mockery::on(function ($api) {
                return 0 === strpos($api, UcloudGateway::ENDPOINT_URL);
            }),
            \Mockery::on(function ($params) {
                return true;
            })
        )
            ->andReturn([
                'RetCode' => UcloudGateway::SUCCESS_CODE,
            ], [
                'RetCode' => 170,
                'Message' => 'Missing signature',
            ])->times(2);

        $message = new Message([
            'template' => '',
            'data' => [
                'code' => '', // 如果是多个参数可以用数组
                'mobiles' => '', // 同时发送多个手机也可以用数组来,[1111111,11111]
            ],
        ]);
        $config = new Config($config);

        $this->assertSame([
            'RetCode' => UcloudGateway::SUCCESS_CODE,
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(170);
        $this->expectExceptionMessage('Missing signature');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }

    public function testSignContent()
    {
        $defaultSigContent = 'default_sig_content';

        $dataSigContent = 'data_sig_content';

        $config = [
            'private_key' => '', // 私钥
            'public_key' => '', // 公钥
            'sig_content' => $defaultSigContent, // 签名
            'project_id' => '', // 默认不填，子账号才需要填
        ];

        $gateway = \Mockery::mock(UcloudGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')->with(
            'get',
            \Mockery::on(function ($api) {
                return 0 === strpos($api, UcloudGateway::ENDPOINT_URL);
            }),
            \Mockery::on(function ($params) {
                return true;
            })
        )
            ->andReturn([
                'RetCode' => UcloudGateway::SUCCESS_CODE,
            ], [
                'RetCode' => 170,
                'Message' => 'Missing signature',
            ])->times(2);

        $message = new Message([
            'template' => '',
            'data' => [
                'code' => '', // 如果是多个参数可以用数组
                'mobiles' => '', // 同时发送多个手机也可以用数组来,[1111111,11111]
                'sig_content' => $dataSigContent,
            ],
        ]);
        $config = new Config($config);

        $this->assertSame([
            'RetCode' => UcloudGateway::SUCCESS_CODE,
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(170);
        $this->expectExceptionMessage('Missing signature');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }

    /**
     * @dataProvider templateParamsProvider
     */
    public function testTemplateParams(array $data, array $templateParams)
    {
        $config = [
            'private_key' => 'private-key',
            'public_key' => 'public-key',
            'sig_content' => 'EasySms',
        ];
        $params = array_merge([
            'Action' => 'SendUSMSMessage',
            'SigContent' => 'EasySms',
            'TemplateId' => 'template-id',
            'PublicKey' => 'public-key',
            'PhoneNumbers.0' => 18888888888,
        ], $templateParams);
        ksort($params);
        $signature = '';
        foreach ($params as $key => $value) {
            $signature .= $key.$value;
        }
        $params['Signature'] = sha1($signature.'private-key');

        $gateway = \Mockery::mock(UcloudGateway::class.'[get]', [$config])->shouldAllowMockingProtectedMethods();
        $gateway->shouldReceive('get')->once()->with(
            UcloudGateway::ENDPOINT_URL,
            \Mockery::on(function ($actual) use ($params) {
                ksort($actual);
                ksort($params);
                $this->assertSame($params, $actual);

                return true;
            })
        )->andReturn(['RetCode' => UcloudGateway::SUCCESS_CODE]);

        $this->assertSame(['RetCode' => UcloudGateway::SUCCESS_CODE], $gateway->send(
            new PhoneNumber(18888888888),
            new Message(['template' => 'template-id', 'data' => $data]),
            new Config($config)
        ));
    }

    public function templateParamsProvider()
    {
        return [
            'missing code' => [[], []],
            'null code' => [['code' => null], []],
            'empty string' => [['code' => ''], []],
            'empty array' => [['code' => []], []],
            'false code' => [['code' => false], []],
            'integer zero' => [['code' => 0], ['TemplateParams.0' => 0]],
            'string zero' => [['code' => '0'], ['TemplateParams.0' => '0']],
            'single parameter' => [['code' => '123456'], ['TemplateParams.0' => '123456']],
            'multiple parameters' => [['code' => ['123456', '10']], ['TemplateParams.0' => '123456', 'TemplateParams.1' => '10']],
            'zero in array' => [['code' => [0, '0']], ['TemplateParams.0' => 0, 'TemplateParams.1' => '0']],
        ];
    }
}
