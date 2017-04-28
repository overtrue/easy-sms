<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Contracts;

use Overtrue\EasySms\Support\Config;

/**
 * Class GatewayInterface.
 */
interface GatewayInterface
{
    /**
     * Send a short message.
     *
     * @param int|string|array                             $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface $message
     * @param \Overtrue\EasySms\Support\Config             $config
     *
     * @return array
     */
    public function send($to, MessageInterface $message, Config $config);
}
