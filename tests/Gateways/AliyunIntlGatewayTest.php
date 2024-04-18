<?php

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Gateways\AliyunIntlGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

/**
 * Class AliyunIntlGatewayTest.
 */
class AliyunIntlGatewayTest extends TestCase
{
    /** @test */
    public function itCanSenSmsByAliyunIntlGateway()
    {
        $config = [
            'access_key_id' => 'mock-api-key',
            'access_key_secret' => 'mock-api-secret',
            'sign_name' => 'mock-api-sign-name',
            'template_code' => 'mock-template-code',
        ];
        $gateway = \Mockery::mock(AliyunIntlGateway::class.'[get]', [$config])->shouldAllowMockingProtectedMethods();

        $expected = [
            'RegionId' => 'ap-southeast-1',
            'AccessKeyId' => 'mock-api-key',
            'Format' => 'JSON',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            // 'SignatureNonce' => uniqid('', true),
            // 'Timestamp' => date('Y-m-d\TH:i:s\Z'),
            'Version' => '2018-05-01',
            'To' => (string) new PhoneNumber(18888888888),
            'Action' => 'SendMessageWithTemplate',
            'From' => 'mock-api-sign-name',
            'TemplateCode' => 'mock-template-code',
            'TemplateParam' => json_encode(['code' => '123456']),
        ];

        $gateway->shouldReceive('get')
                ->with(AliyunIntlGateway::ENDPOINT_URL, \Mockery::on(function ($params) use ($expected) {
                    if (empty($params['Signature'])) {
                        return false;
                    }

                    unset($params['SignatureNonce'], $params['Timestamp'], $params['Signature']);

                    ksort($params);
                    ksort($expected);

                    return $params == $expected;
                }))
                ->andReturn([
                    'ResponseCode' => 'OK',
                    'ResponseDescription' => 'mock-result',
                ], [
                    'ResponseCode' => 1234,
                    'ResponseDescription' => 'mock-err-msg',
                ])
                ->twice();

        $message = new Message([
            'template' => 'mock-template-code',
            'data' => ['code' => '123456'],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'ResponseCode' => 'OK',
            'ResponseDescription' => 'mock-result',
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(1234);
        $this->expectExceptionMessage('mock-err-msg');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
