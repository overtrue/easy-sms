<?php

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\GatewayInterface;

/**
 * Class LogGateway
 */
class LogGateway implements GatewayInterface
{
    /**
     * Send a short message.
     *
     * @param string|int $to
     * @param string     $template
     * @param array      $data
     *
     * @return mixed
     */
    public function send($to, $template, array $data = [])
    {
        // TODO: Implement send() method.
    }
}