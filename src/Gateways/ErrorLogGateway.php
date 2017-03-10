<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Gateways;

/**
 * Class ErrorLogGateway.
 */
class ErrorLogGateway extends Gateway
{
    /**
     * Send a short message.
     *
     * @param string|int $to
     * @param string     $message
     * @param array      $data
     *
     * @return mixed
     */
    public function send($to, $message, array $data = [])
    {
        $message = sprintf(
            "[%s] to: %s, message: \"%s\", data: %s\n",
            date('Y-m-d H:i:s'),
            $to,
            addcslashes($message, '"'),
            json_encode($data)
        );

        return error_log($message, 3, $this->config->get('file', ini_get('error_log')));
    }
}
