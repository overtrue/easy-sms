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
use Overtrue\EasySms\Gateways\QcloudGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class QcloudGatewayTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'sdk_app_id' => 'mock-sdk-app-id',
            'secret_key' => 'mock-secret-key',
            'secret_id' => 'mock-secret-id',
            'sign_name' => 'mock-api-sign-name',
        ];

        $gateway = \Mockery::mock(QcloudGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
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

    public function testSendWithPartialErrors()
    {
        $config = [
            'sdk_app_id' => 'mock-sdk-app-id',
            'secret_key' => 'mock-secret-key',
            'secret_id' => 'mock-secret-id',
            'sign_name' => 'mock-api-sign-name',
        ];

        $gateway = \Mockery::mock(QcloudGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
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
}
