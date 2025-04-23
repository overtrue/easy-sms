<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms;

/**
 * Class PhoneNumberInterface.
 *
 * @author overtrue <i@overtrue.me>
 */
class PhoneNumber implements Contracts\PhoneNumberInterface
{
    protected int|string $number;

    protected ?int $IDDCode;

    /**
     * PhoneNumberInterface constructor.
     */
    public function __construct(int|string $numberWithoutIDDCode, ?string $IDDCode = null)
    {
        $this->number = $numberWithoutIDDCode;
        $this->IDDCode = $IDDCode ? intval(ltrim($IDDCode, '+0')) : null;
    }

    /**
     * 86.
     */
    public function getIDDCode(): ?int
    {
        return $this->IDDCode;
    }

    /**
     * 18888888888.
     */
    public function getNumber(): int|string
    {
        return $this->number;
    }

    /**
     * +8618888888888.
     */
    public function getUniversalNumber(): string
    {
        return $this->getPrefixedIDDCode('+').$this->number;
    }

    /**
     * 008618888888888.
     */
    public function getZeroPrefixedNumber(): string
    {
        return $this->getPrefixedIDDCode('00').$this->number;
    }

    public function getPrefixedIDDCode(string $prefix): ?string
    {
        return $this->IDDCode ? $prefix.$this->IDDCode : null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUniversalNumber();
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see  http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->getUniversalNumber();
    }

    /**
     * Check if the phone number belongs to chinese mainland.
     */
    public function inChineseMainland(): bool
    {
        return empty($this->IDDCode) || 86 === $this->IDDCode;
    }
}
