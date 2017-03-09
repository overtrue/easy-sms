<?php

namespace Overtrue\EasySms\Tests;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    public function tearDown()
    {
        parent::tearDown();

        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        Mockery::close();
    }
}