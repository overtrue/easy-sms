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

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

/**
 * Class Messenger.
 */
class Messenger
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILURE = 'failure';

    protected EasySms $easySms;

    /**
     * Messenger constructor.
     */
    public function __construct(EasySms $easySms)
    {
        $this->easySms = $easySms;
    }

    /**
     * Send a message.
     *
     * @throws InvalidArgumentException
     * @throws NoGatewayAvailableException
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, array $gateways = []): array
    {
        $results = [];
        $isSuccessful = false;

        foreach ($gateways as $gateway => $config) {
            try {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => self::STATUS_SUCCESS,
                    'template' => $message->getTemplate($this->easySms->gateway($gateway)),
                    'result' => $this->easySms->gateway($gateway)->send($to, $message, $config),
                ];
                $isSuccessful = true;

                break;
            } catch (\Exception|\Throwable $e) {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => self::STATUS_FAILURE,
                    'template' => $message->getTemplate($this->easySms->gateway($gateway)),
                    'exception' => $e,
                ];
            }
        }

        if (!$isSuccessful) {
            throw new NoGatewayAvailableException($results);
        }

        return $results;
    }
}
