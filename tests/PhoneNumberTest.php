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

use Overtrue\EasySms\PhoneNumber;

/**
 * Class PhoneNumberTest.
 *
 * @author overtrue <i@overtrue.me>
 */
class PhoneNumberTest extends TestCase
{
    public function test_only_number()
    {
        $n = new PhoneNumber(18888888888);
        $this->assertSame(18888888888, $n->getNumber());
        $this->assertNull($n->getIDDCode());
        $this->assertSame('18888888888', $n->getUniversalNumber());
        $this->assertSame('18888888888', $n->getZeroPrefixedNumber());
        $this->assertSame('18888888888', \strval($n));
    }

    public function test_diff_code()
    {
        $n = new PhoneNumber(18888888888, 68);
        $this->assertSame(68, $n->getIDDCode());

        $n = new PhoneNumber(18888888888, '+68');
        $this->assertSame(68, $n->getIDDCode());

        $n = new PhoneNumber(18888888888, '0068');
        $this->assertSame(68, $n->getIDDCode());
    }

    public function test_json_encode()
    {
        $n = new PhoneNumber(18888888888, 68);
        $this->assertSame(json_encode(['number' => $n->getUniversalNumber()]), \json_encode(['number' => $n]));
    }

    public function test_international_format()
    {
        // Test international format with +
        $n = new PhoneNumber('+8618888888888');
        $this->assertSame(86, $n->getIDDCode());
        $this->assertSame('18888888888', $n->getNumber());
        $this->assertSame('+8618888888888', $n->getUniversalNumber());
        $this->assertSame('008618888888888', $n->getZeroPrefixedNumber());
    }

    public function test_international_format_without_plus()
    {
        // Test international format starting with 00
        $n = new PhoneNumber('008618888888888');
        $this->assertSame(86, $n->getIDDCode());
        $this->assertSame('18888888888', $n->getNumber());
        $this->assertSame('+8618888888888', $n->getUniversalNumber());
    }

    public function test_different_countries()
    {
        // Test US number
        $n = new PhoneNumber('+1 650 253 0000');
        $this->assertSame(1, $n->getIDDCode());
        $this->assertSame('+16502530000', $n->getUniversalNumber());

        // Test Netherlands number
        $n = new PhoneNumber('+31612345678');
        $this->assertSame(31, $n->getIDDCode());
        $this->assertSame('+31612345678', $n->getUniversalNumber());

        // Test UK number
        $n = new PhoneNumber('+44 117 496 0123');
        $this->assertSame(44, $n->getIDDCode());
        $this->assertSame('+441174960123', $n->getUniversalNumber());
    }

    public function test_chinese_mainland_check()
    {
        $n = new PhoneNumber(18888888888);
        $this->assertTrue($n->inChineseMainland());

        $n = new PhoneNumber(18888888888, 86);
        $this->assertTrue($n->inChineseMainland());

        $n = new PhoneNumber(18888888888, 1);
        $this->assertFalse($n->inChineseMainland());
    }
}
