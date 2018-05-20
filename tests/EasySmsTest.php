<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests;

use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Messenger;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use RuntimeException;

class EasySmsTest extends TestCase
{
    public function testGateway()
    {
        $easySms = new EasySms([]);

        $this->assertInstanceOf(GatewayInterface::class, $easySms->gateway('error-log'));

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
            sprintf(
                'Gateway "%s" not inherited from %s.',
                DummyInvalidGatewayForTest::class,
                GatewayInterface::class
            )
        );
        $easySms->gateway();
    }

    public function testExtend()
    {
        $easySms = new EasySms([]);
        $easySms->extend('foo', function () {
            return new DummyGatewayForTest();
        });

        $this->assertInstanceOf(DummyGatewayForTest::class, $easySms->gateway('foo'));
    }

    public function testSend()
    {
        $messenger = \Mockery::mock(Messenger::class);
        $messenger->allows()->send(\Mockery::on(function ($number) {
            return $number instanceof PhoneNumber && !empty($number->getNumber());
        }), \Mockery::on(function ($message) {
            return $message instanceof MessageInterface && !empty($message->getContent());
        }), [])->andReturn('send-result');

        $easySms = \Mockery::mock(EasySms::class.'[getMessenger]', [['default' => DummyGatewayForTest::class]]);
        $easySms->shouldReceive('getMessenger')->andReturn($messenger);

        // simple
        $this->assertSame('send-result', $easySms->send('18888888888', ['content' => 'hello']));

        // message object
        $message = new Message(['content' => 'hello']);
        $this->assertSame('send-result', $easySms->send('18888888888', $message, []));

        // phone number object
        $number = new PhoneNumber('18888888888', 35);
        $message = new Message(['content' => 'hello']);
        $messenger = \Mockery::mock(Messenger::class);
        $messenger->expects()->send($number, $message, [])->andReturn('mock-result');
        $easySms = \Mockery::mock(EasySms::class.'[getMessenger]', [['default' => DummyGatewayForTest::class]]);
        $easySms->shouldReceive('getMessenger')->andReturn($messenger);
        $this->assertSame('mock-result', $easySms->send($number, $message));
    }

    public function testGetMessenger()
    {
        $easySms = new EasySms([]);

        $this->assertInstanceOf(Messenger::class, $easySms->getMessenger());
    }
}

class DummyGatewayForTest implements GatewayInterface
{
    public function getName()
    {
        return 'name';
    }

    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        return 'send-result';
    }
}

class DummyInvalidGatewayForTest
{
    // nothing
}
