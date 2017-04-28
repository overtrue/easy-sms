<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Support\Config;

/**
 * Class Messenger.
 */
class Messenger
{
    /**
     * Send a message.
     *
     * @param string                                       $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface $message
     * @param array                                        $gateways
     *
     * @return array
     */
    public function send($to, MessageInterface $message, array $gateways = [])
    {
        if (!($message instanceof MessageInterface)) {
            $message = new Message(['content' => $message, 'template' => $message]);
        }

        $result = [];

        foreach ($gateways as $gateway => $config) {
            try {
                $result[$gateway] = $this->gateway($gateway)->send($to, $message, new Config($config));
            } catch (GatewayErrorException $e) {
                $result[$gateway] = $e;
                continue;
            }
        }

        return false;
    }
}
