<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Exceptions;

use Throwable;

/**
 * Class NoGatewayAvailableException.
 *
 * @author overtrue <i@overtrue.me>
 */
class NoGatewayAvailableException extends Exception
{
    /**
     * @var array
     */
    public $results = [];

    /**
     * NoGatewayAvailableException constructor.
     *
     * @param array           $results
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct(array $results = [], $code = 0, Throwable $previous = null)
    {
        $this->results = $results;
        parent::__construct('All the gateways have failed.', $code, $previous);
    }
}
