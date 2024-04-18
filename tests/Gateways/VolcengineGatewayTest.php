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
use Overtrue\EasySms\Gateways\VolcengineGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class VolcengineGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'access_key_id' => 'mock_access_key_id',
            'access_key_secret' => 'mock_access_key_secret',
            'sign_name' => 'mock_sign_name',
            'sms_account' => 'mock_sms_account',
        ];

        $queries = [
            'Action' => VolcengineGateway::ENDPOINT_ACTION,
            'Version' => VolcengineGateway::ENDPOINT_VERSION,
        ];

        $templateId = 'mock_template_id';
        $phone = '18888888888';
        $templateParam = ['code' => '1234'];

        $params = [
            'SmsAccount' => $config['sms_account'],
            'Sign' => $config['sign_name'],
            'TemplateID' => $templateId,
            'TemplateParam' => json_encode($templateParam),
            'PhoneNumbers' => $phone,
        ];

        $successReturn = [
            'ResponseMetadata' => [
                'RequestId' => 'mock_request_id',
                'Action' => VolcengineGateway::ENDPOINT_ACTION,
                'Version' => VolcengineGateway::ENDPOINT_VERSION,
                'Service' => VolcengineGateway::ENDPOINT_SERVICE,
                'Region' => VolcengineGateway::ENDPOINT_DEFAULT_REGION_ID,
            ],
            'Result' => [
                'MessageID' => ['mock_message_id'],
            ],
        ];

        $failedReturn = [
            'ResponseMetadata' => [
                'RequestId' => 'mock_request_id',
                'Action' => VolcengineGateway::ENDPOINT_ACTION,
                'Version' => VolcengineGateway::ENDPOINT_VERSION,
                'Service' => VolcengineGateway::ENDPOINT_SERVICE,
                'Region' => VolcengineGateway::ENDPOINT_DEFAULT_REGION_ID,
                'Error' => [
                    'Code' => str_repeat('ZJ', rand(1, 3)).rand(10000, 30000),
                    'Message' => 'mock_error_message',
                ],
            ],
        ];

        $gateway = \Mockery::mock(VolcengineGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();
        $gateway->shouldReceive('request')
            ->with(
                'post',
                VolcengineGateway::$endpoints[VolcengineGateway::ENDPOINT_DEFAULT_REGION_ID].'/',
                [
                    'query' => $queries,
                    'json' => $params,
                ]
            )
            ->andReturn($successReturn, $failedReturn)
            ->twice();

        $message = new Message([
            'template' => $templateId,
            'data' => $templateParam,
        ]);

        $this->assertSame($successReturn, $gateway->send(new PhoneNumber($phone), $message, new Config($config)));

        $message = new Message([
            'template' => $templateId,
            'data' => $templateParam,
        ]);

        $this->expectException(GatewayErrorException::class);
        $gateway->send(new PhoneNumber($phone), $message, new Config($config));
    }
}
