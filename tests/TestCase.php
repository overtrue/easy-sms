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

use Mockery;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        Mockery::globalHelpers();
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
