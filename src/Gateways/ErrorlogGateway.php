<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Support\Config;

/**
 * Class ErrorlogGateway.
 */
class ErrorlogGateway extends Gateway
{
    /**
     * @return array
     */
    public function send(PhoneNumberInterface|array $to, MessageInterface $message, Config $config)
    {
        if (is_array($to)) {
            $to = implode(',', $to);
        }

        $message = sprintf(
            "[%s] to: %s | message: \"%s\"  | template: \"%s\" | data: %s\n",
            date('Y-m-d H:i:s'),
            $to,
            $message->getContent($this),
            $message->getTemplate($this),
            json_encode($message->getData($this))
        );

        $file = $this->config->get('file', ini_get('error_log'));
        $status = error_log($message, 3, $file);

        return compact('status', 'file');
    }
}
