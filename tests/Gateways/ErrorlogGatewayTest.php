<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Gateways\ErrorlogGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class ErrorlogGatewayTest extends TestCase
{
    protected $logFile = 'easy-sms-error-log-mock-file.log';

    public function tearDown()
    {
        parent::tearDown();
        unlink($this->logFile);
    }

    public function testSend()
    {
        $gateway = new ErrorlogGateway([
            'file' => $this->logFile,
        ]);

        $message = new Message([
            'content' => 'This is a test message.',
            'data' => ['foo' => 'bar'],
        ]);

        $gateway->send(18188888888, $message, new Config());

        $this->assertTrue(file_exists($this->logFile));
        $this->assertContains(
            'to: 18188888888 | message: "This is a test message."  | template: "" | data: {"foo":"bar"}',
            file_get_contents($this->logFile)
        );
    }
}
