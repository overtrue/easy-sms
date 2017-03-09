<?php

namespace Overtrue\EasySms\Contracts;

/**
 * Class GatewayInterface
 */
interface GatewayInterface
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
    public function send($to, $template, array $data = []);
}