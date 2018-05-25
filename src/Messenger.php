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
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\Support\Config;

/**
 * Class Messenger.
 */
class Messenger
{
    const STATUS_SUCCESS = 'success';

    const STATUS_FAILURE = 'failure';

    /**
     * @var \Overtrue\EasySms\EasySms
     */
    protected $easySms;

    /**
     * Messenger constructor.
     *
     * @param \Overtrue\EasySms\EasySms $easySms
     */
    public function __construct(EasySms $easySms)
    {
        $this->easySms = $easySms;
    }

    /**
     * Send a message.
     *
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface     $message
     * @param array                                            $gateways
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\InvalidArgumentException
     * @throws \Overtrue\EasySms\Exceptions\NoGatewayAvailableException
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, array $gateways = [])
    {
        if (empty($gateways)) {
            $gateways = $message->getGateways();
        }

        if (empty($gateways)) {
            $gateways = $this->easySms->getConfig()->get('default.gateways', []);
        }

        $gateways = $this->formatGateways($gateways);
        $strategyAppliedGateways = $this->easySms->strategy()->apply($gateways);

        $results = [];
        $isSuccessful = false;
        foreach ($strategyAppliedGateways as $gateway) {
            try {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => self::STATUS_SUCCESS,
                    'result' => $this->easySms->gateway($gateway)->send($to, $message, new Config($gateways[$gateway])),
                ];
                $isSuccessful = true;

                break;
            } catch (\Throwable $e) {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => self::STATUS_FAILURE,
                    'exception' => $e,
                ];
            } catch (\Exception $e) {
                $results[$gateway] = [
                    'gateway' => $gateway,
                    'status' => self::STATUS_FAILURE,
                    'exception' => $e,
                ];
            }
        }

        if (!$isSuccessful) {
            throw new NoGatewayAvailableException($results);
        }

        return $results;
    }

    /**
     * @param array $gateways
     *
     * @return array
     */
    protected function formatGateways(array $gateways)
    {
        $formatted = [];
        $config = $this->easySms->getConfig();

        foreach ($gateways as $gateway => $setting) {
            if (is_int($gateway) && is_string($setting)) {
                $gateway = $setting;
                $setting = [];
            }

            $formatted[$gateway] = $setting;
            $globalSetting = $config->get("gateways.{$gateway}", []);

            if (is_string($gateway) && !empty($globalSetting) && is_array($setting)) {
                $formatted[$gateway] = array_merge($globalSetting, $setting);
            }
        }

        return $formatted;
    }
}
