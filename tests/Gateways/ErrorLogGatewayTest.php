<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Gateways\ErrorLogGateway;
use Overtrue\EasySms\Tests\TestCase;

class ErrorLogGatewayTest extends TestCase
{
    protected $logFile = 'easy-sms-error-log-mock-file.log';

    public function tearDown()
    {
        parent::tearDown();
        unlink($this->logFile);
    }

    public function testSend()
    {
        $gateway = new ErrorLogGateway([
            'file' => $this->logFile,
        ]);

        $gateway->send(18188888888, 'This is a test message.', ['foo' => 'bar']);

        $this->assertTrue(file_exists($this->logFile));
        $this->assertContains(
            "to: 18188888888, message: \"This is a test message.\", data: {\"foo\":\"bar\"}\n",
            file_get_contents($this->logFile)
        );
    }
}
