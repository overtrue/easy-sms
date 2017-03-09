<?php

namespace Overtrue\EasySms\Tests;

use RuntimeException;
use InvalidArgumentException;
use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\EasySms;

class EasySmsTest extends TestCase
{
    public function testGateway()
    {
        $easySms = new EasySms([]);

        $this->assertInstanceOf(GatewayInterface::class, $easySms->gateway('Log'));

        // invalid gateway
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Gateway "Overtrue\EasySms\Gateways\NotExistsGatewayNameGateway" not exists.');

        $easySms->gateway('NotExistsGatewayName');
    }

    public function testGatewayWithoutDefaultSetting()
    {
        $easySms = new EasySms([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No default gateway configured.');

        $easySms->gateway();
    }

    public function testGatewayWithDefaultSetting()
    {
        $easySms = new EasySms(['default' => DummyGatewayForTest::class]);
        $this->assertSame(DummyGatewayForTest::class, $easySms->getDefaultGateway());
        $this->assertInstanceOf(DummyGatewayForTest::class, $easySms->gateway());

        // invalid gateway
        $easySms->setDefaultGateway(DummyInvalidGatewayForTest::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Gateway "%s" not inherited from %s.',
                DummyInvalidGatewayForTest::class,
                GatewayInterface::class)
        );
        $easySms->gateway();
    }

    public function testExtend()
    {
        $easySms = new EasySms([]);
        $easySms->extend('foo', function() {
            return new DummyGatewayForTest();
        });

        $this->assertInstanceOf(DummyGatewayForTest::class, $easySms->gateway('foo'));
    }

    public function testMagicCall()
    {
        $easySms = new EasySms(['default' => DummyGatewayForTest::class]);

        $this->assertSame('send-result', $easySms->send('mock-number', 'hello'));
    }
}

class DummyGatewayForTest implements GatewayInterface {
    public function send()
    {
        return 'send-result';
    }
}

class DummyInvalidGatewayForTest {
    // nothing
}
