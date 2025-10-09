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

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Gateways\Gateway;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class GatewayTest extends TestCase
{
    public function test_timeout()
    {
        $gateway = new DummyGatewayForGatewayTest(['foo' => 'bar']);

        $this->assertInstanceOf(Config::class, $gateway->getConfig());
        $this->assertSame(5.0, $gateway->getTimeout());
        $gateway->setTimeout(4.0);
        $this->assertSame(4.0, $gateway->getTimeout());

        $gateway = new DummyGatewayForGatewayTest(['foo' => 'bar', 'timeout' => 12.0]);
        $this->assertSame(12.0, $gateway->getTimeout());
    }

    public function test_config_setter_and_getter()
    {
        $gateway = new DummyGatewayForGatewayTest(['foo' => 'bar']);

        $this->assertInstanceOf(Config::class, $gateway->getConfig());

        $config = new Config(['name' => 'overtrue']);
        $this->assertSame($config, $gateway->setConfig($config)->getConfig());
    }
}

class DummyGatewayForGatewayTest extends Gateway
{
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        return 'mock-result';
    }
}
