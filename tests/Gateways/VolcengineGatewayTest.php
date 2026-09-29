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

use GuzzleHttp\Psr7\Request;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Gateways\VolcengineGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class VolcengineGatewayTest extends TestCase
{
    public function test_send()
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

    /**
     * The signature middleware relies on HandlerStack, PSR-7 helpers and
     * stream hashing, all of which must keep working on Guzzle 8 / PSR-7 3.x.
     */
    public function test_sign_handle()
    {
        $gateway = new VolcengineGatewayForSignHandleTest([
            'access_key_id' => 'mock_access_key_id',
            'access_key_secret' => 'mock_access_key_secret',
            'sign_name' => 'mock_sign_name',
            'sms_account' => 'mock_sms_account',
        ]);

        $captured = null;
        $handler = function ($request, $options) use (&$captured) {
            $captured = $request;

            return 'signed-result';
        };

        $middleware = $gateway->signHandle();
        $send = $middleware($handler);

        $result = $send(new Request(
            'POST',
            VolcengineGateway::$endpoints[VolcengineGateway::ENDPOINT_DEFAULT_REGION_ID].'/?Action=SendSms&Version=2020-01-01',
            ['Content-Type' => VolcengineGateway::ENDPOINT_CONTENT_TYPE],
            '{"SmsAccount":"mock_sms_account"}'
        ), []);

        $this->assertSame('signed-result', $result);
        $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z$/', $captured->getHeaderLine('X-Date'));
        $this->assertStringStartsWith(
            'HMAC-SHA256 Credential=mock_access_key_id/',
            $captured->getHeaderLine('Authorization')
        );
        $this->assertStringContainsString('SignedHeaders=', $captured->getHeaderLine('Authorization'));
    }
}

class VolcengineGatewayForSignHandleTest extends VolcengineGateway
{
    public function signHandle()
    {
        return parent::signHandle();
    }
}
