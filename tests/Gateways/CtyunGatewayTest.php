<?php

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Gateways\CtyunGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class CtyunGatewayTest extends TestCase
{
    public function test_send()
    {
        $config = [
            'secret_key' => 'mock-secrey-key',
            'access_key' => 'mock-access-key',
        ];
        $gateway = \Mockery::mock(CtyunGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();

        $gateway->shouldReceive('request')
            ->andReturn([
                'code' => CtyunGateway::SUCCESS_CODE,
            ], [
                'code' => 'FAIL',
                'message' => 'error',
                'requestId' => 'cv7ai1fagnl5nmbiuil0',
            ])->twice();

        $message = new Message([
            'content' => 'mock-content',
            'template' => 'mock-tpl-id', // 模板ID
            'data' => [
                'code' => 123456,
            ],
        ]);

        $config = new Config($config);

        $this->assertSame([
            'code' => CtyunGateway::SUCCESS_CODE,
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionMessage('error');

        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
