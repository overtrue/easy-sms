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

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Gateways\QcloudGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class QcloudGatewayTest extends TestCase
{
    public function test_send()
    {
        $config = [
            'sdk_app_id' => 'mock-sdk-app-id',
            'secret_key' => 'mock-secret-key',
            'secret_id' => 'mock-secret-id',
            'sign_name' => 'mock-api-sign-name',
        ];

        $gateway = \Mockery::mock(QcloudGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->with('post', \Mockery::type('string'), \Mockery::on(function ($options) {
                // guzzle 7.11+ requires header values to be strings
                return array_filter($options['headers'], 'is_string') === $options['headers'];
            }))
            ->andReturn([
                'Response' => [
                    'SendStatusSet' => [
                        [
                            'SerialNo' => '2028:f825e6b16e23f73f4123',
                            'PhoneNumber' => '8618888888888',
                            'Fee' => 1,
                            'SessionContext' => '',
                            'Code' => 'Ok',
                            'Message' => 'send success',
                            'IsoCode' => 'CN',
                        ],
                    ],
                ],
                'RequestId' => '0dc99542-c61a-4a16-9545-ec8ec202c543',
            ], [
                'Response' => [
                    'Error' => [
                        'Code' => 'AuthFailure.SignatureFailure',
                        'Message' => 'The provided credentials could not be validated. Please check your signature is correct.',
                    ],
                ],
                'RequestId' => '0dc99542-c61a-4a16-9545-2b967e2c980a',
            ])->twice();

        $message = new Message([
            'template' => 'template-id',
            'data' => [
                '888888',
            ],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'Response' => [
                'SendStatusSet' => [
                    [
                        'SerialNo' => '2028:f825e6b16e23f73f4123',
                        'PhoneNumber' => '8618888888888',
                        'Fee' => 1,
                        'SessionContext' => '',
                        'Code' => 'Ok',
                        'Message' => 'send success',
                        'IsoCode' => 'CN',
                    ],
                ],
            ],
            'RequestId' => '0dc99542-c61a-4a16-9545-ec8ec202c543',
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('The provided credentials could not be validated. Please check your signature is correct.');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }

    public function test_send_with_partial_errors()
    {
        $config = [
            'sdk_app_id' => 'mock-sdk-app-id',
            'secret_key' => 'mock-secret-key',
            'secret_id' => 'mock-secret-id',
            'sign_name' => 'mock-api-sign-name',
        ];

        $gateway = \Mockery::mock(QcloudGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->with('post', \Mockery::type('string'), \Mockery::on(function ($options) {
                // guzzle 7.11+ requires header values to be strings
                return array_filter($options['headers'], 'is_string') === $options['headers'];
            }))
            ->andReturn([
                'Response' => [
                    'SendStatusSet' => [
                        [
                            'SerialNo' => '2028:f825e6b16e23f73f4123',
                            'PhoneNumber' => '8618888888888',
                            'Fee' => 1,
                            'SessionContext' => '',
                            'Code' => 'InvalidParameterValue.TemplateParameterFormatError',
                            'Message' => 'Verification code template parameter format error',
                            'IsoCode' => 'CN',
                        ],
                    ],
                ],
                'RequestId' => '0dc99542-c61a-4a16-9545-ec8ec202c543',
            ])->once();

        $message = new Message([
            'template' => 'template-id',
            'data' => [
                '888888',
            ],
        ]);

        $config = new Config($config);

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Verification code template parameter format error');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }

    /**
     * Guzzle 7.11+/8.x rejects non-string header values, so the request has to
     * be built by a real client to be sure the headers are typed correctly.
     */
    public function test_send_with_a_real_guzzle_client()
    {
        $config = [
            'sdk_app_id' => 'mock-sdk-app-id',
            'secret_key' => 'mock-secret-key',
            'secret_id' => 'mock-secret-id',
            'sign_name' => 'mock-sign-name',
        ];

        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'Response' => [
                    'SendStatusSet' => [
                        ['Code' => 'Ok', 'Message' => 'send success'],
                    ],
                ],
            ])),
        ]));
        $stack->push(Middleware::history($history));

        $gateway = new QcloudGateway($config);
        $gateway->setGuzzleOptions(['handler' => $stack]);

        $message = new Message([
            'template' => 'template-id',
            'data' => [
                '888888',
            ],
        ]);

        $result = $gateway->send(new PhoneNumber('18888888888'), $message, new Config($config));

        $this->assertSame('Ok', $result['Response']['SendStatusSet'][0]['Code']);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertMatchesRegularExpression('/^\d+$/', $request->getHeaderLine('X-TC-Timestamp'));
        $this->assertSame('sms.tencentcloudapi.com', $request->getHeaderLine('Host'));
    }
}
