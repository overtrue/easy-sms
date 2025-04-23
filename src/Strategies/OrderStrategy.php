<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Strategies;

use Overtrue\EasySms\Contracts\StrategyInterface;

/**
 * Class OrderStrategy.
 */
class OrderStrategy implements StrategyInterface
{
    /**
     * Apply the strategy and return result.
     */
    public function apply(array $gateways): array
    {
        return array_keys($gateways);
    }
}
