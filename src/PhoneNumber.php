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

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Class PhoneNumberInterface.
 *
 * @author overtrue <i@overtrue.me>
 */
class PhoneNumber implements Contracts\PhoneNumberInterface
{
    protected int|string $number;

    protected ?int $IDDCode;

    protected ?\libphonenumber\PhoneNumber $phoneNumberObject = null;

    protected PhoneNumberUtil $phoneUtil;

    /**
     * PhoneNumberInterface constructor.
     */
    public function __construct(int|string $numberWithoutIDDCode, ?string $IDDCode = null)
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
        $numberStr = (string) $numberWithoutIDDCode;
        $parsedIDDCode = $IDDCode ? intval(ltrim($IDDCode, '+0')) : null;

        // Try to parse using libphonenumber
        try {
            if (null !== $parsedIDDCode) {
                // If IDD code is provided, construct the phone number directly
                $this->phoneNumberObject = new \libphonenumber\PhoneNumber();
                $this->phoneNumberObject->setCountryCode($parsedIDDCode);
                $this->phoneNumberObject->setNationalNumber($numberStr);

                $this->IDDCode = $parsedIDDCode;
                $this->number = is_numeric($numberWithoutIDDCode) && is_int($numberWithoutIDDCode) ? $numberWithoutIDDCode : $numberStr;
            } elseif (str_starts_with($numberStr, '+')) {
                // International format with +
                $this->phoneNumberObject = $this->phoneUtil->parse($numberStr, null);
                $this->IDDCode = $this->phoneNumberObject->getCountryCode();
                $this->number = $this->phoneNumberObject->getNationalNumber();
            } elseif (str_starts_with($numberStr, '00')) {
                // International format with 00 prefix - need to provide a region for parsing
                // Try parsing with common regions
                $this->phoneNumberObject = $this->phoneUtil->parse($numberStr, 'CN');
                $this->IDDCode = $this->phoneNumberObject->getCountryCode();
                $this->number = $this->phoneNumberObject->getNationalNumber();
            } else {
                // No IDD code provided and no international prefix
                // Keep the number as-is without parsing (backward compatibility)
                $this->number = $numberWithoutIDDCode;
                $this->IDDCode = null;

                // But still try to parse for validation purposes
                try {
                    $this->phoneNumberObject = $this->phoneUtil->parse($numberStr, 'CN');
                } catch (NumberParseException $e) {
                    // Ignore parsing errors for backward compatibility
                }
            }
        } catch (NumberParseException $e) {
            // If parsing fails, fall back to storing the raw values
            $this->number = $numberWithoutIDDCode;
            $this->IDDCode = $parsedIDDCode;
        }
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
        if (null !== $this->phoneNumberObject && null !== $this->IDDCode && $this->phoneUtil->isValidNumber($this->phoneNumberObject)) {
            return $this->phoneUtil->format($this->phoneNumberObject, PhoneNumberFormat::E164);
        }

        return $this->getPrefixedIDDCode('+').$this->number;
    }

    /**
     * 008618888888888.
     */
    public function getZeroPrefixedNumber(): string
    {
        if (null !== $this->phoneNumberObject && null !== $this->IDDCode) {
            $e164 = $this->phoneUtil->format($this->phoneNumberObject, PhoneNumberFormat::E164);

            // Convert +XX to 00XX
            return '00'.substr($e164, 1);
        }

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
