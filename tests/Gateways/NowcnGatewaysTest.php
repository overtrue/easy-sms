<?php

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Gateways\NowcnGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class NowcnGatewaysTest extends TestCase
{
    public function test_send()
    {
        $config = [
            'key' => '1',
            'secret' => '1',
            'api_type' => '3',
        ];

        $gateway = \Mockery::mock(NowcnGateway::class.'[request]', [$config])->shouldAllowMockingProtectedMethods();
        $gateway->shouldReceive('request')->with(
            'get',
            \Mockery::on(function ($api) {
                return strpos($api, NowcnGateway::ENDPOINT_URL) === 0;
            }),
            \Mockery::on(function ($params) {
                return true;
            })
        )->andReturn([
            'code' => NowcnGateway::SUCCESS_CODE,
        ], [
            'code' => '-4',
            'msg' => 'authorize failed',
        ])->times(2);

        $message = new Message([
            'content' => 'mock-content',
        ]);
        $config = new Config($config);
        $this->assertSame([
            'code' => NowcnGateway::SUCCESS_CODE,
        ], $gateway->send(new PhoneNumber(18888888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(-4);
        $this->expectExceptionMessage('authorize failed');
        $gateway->send(new PhoneNumber(18888888888), $message, $config);
    }
}
