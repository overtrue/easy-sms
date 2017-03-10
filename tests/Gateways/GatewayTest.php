<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Gateways\Gateway;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class GatewayTest extends TestCase
{
    public function testBaseApi()
    {
        $gateway = new DummyGatewayForGatewayTest(['foo' => 'bar']);

        $this->assertInstanceOf(Config::class, $gateway->getConfig());
        $this->assertSame('https://mock-base-uri', $gateway->getBaseUri());
        $this->assertSame(5.0, $gateway->getTimeout());
        $gateway->timeout(4.0);
        $this->assertSame(4.0, $gateway->getTimeout());

        $gateway = new DummyGatewayForGatewayTest(['foo' => 'bar', 'timeout' => 12.0]);
        $this->assertSame(12.0, $gateway->getTimeout());
    }
}

class DummyGatewayForGatewayTest extends Gateway
{
    protected $baseUri = 'https://mock-base-uri';

    public function send($to, $message, array $data = [])
    {
        return 'mock-result';
    }
}
