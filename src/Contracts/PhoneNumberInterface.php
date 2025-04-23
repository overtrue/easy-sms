<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Contracts;

/**
 * Interface PhoneNumberInterface.
 *
 * @author overtrue <i@overtrue.me>
 */
interface PhoneNumberInterface extends \JsonSerializable
{
    /**
     * 86.
     */
    public function getIDDCode(): ?int;

    /**
     * 18888888888.
     */
    public function getNumber(): int|string;

    /**
     * +8618888888888.
     */
    public function getUniversalNumber(): string;

    /**
     * 008618888888888.
     */
    public function getZeroPrefixedNumber(): string;

    /**
     * @return string
     */
    public function __toString();
}
