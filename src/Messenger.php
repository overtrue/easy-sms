<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\Support\Config;

/**
 * Class Messenger.
 */
class Messenger
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERRED = 'erred';

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
     * @param string|array                                              $to
     * @param string|array|\Overtrue\EasySms\Contracts\MessageInterface $message
     * @param array                                                     $gateways
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\NoGatewayAvailableException
     */
    public function send($to, $message, array $gateways = [])
    {
        $message = $this->formatMessage($message);

        if (empty($gateways)) {
            $gateways = $message->getGateways();
        }

        if (empty($gateways)) {
            $gateways = $this->easySms->getConfig()->get('default.gateways', []);
        }

        $gateways = $this->formatGateways($gateways);
        $strategyAppliedGateways = $this->easySms->strategy()->apply($gateways);

        $results = [];
        $hasSucceed = false;
        foreach ($strategyAppliedGateways as $gateway) {
            try {
                $results[$gateway] = [
                    'status' => self::STATUS_SUCCESS,
                    'result' => $this->easySms->gateway($gateway)->send($to, $message, new Config($gateways[$gateway])),
                ];
                $hasSucceed = true;
                break;
            } catch (GatewayErrorException $e) {
                $results[$gateway] = [
                    'status' => self::STATUS_ERRED,
                    'exception' => $e,
                ];
                continue;
            }
        }

        if (!$hasSucceed) {
            throw new NoGatewayAvailableException($results);
        }

        return $results;
    }

    /**
     * @param array|string|\Overtrue\EasySms\Contracts\MessageInterface $message
     *
     * @return \Overtrue\EasySms\Contracts\MessageInterface
     */
    protected function formatMessage($message)
    {
        if (!($message instanceof MessageInterface)) {
            if (!is_array($message)) {
                $message = [
                    'content' => strval($message),
                    'template' => strval($message),
                ];
            }

            $message = new Message($message);
        }

        return $message;
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
