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

use Throwable;
use Random\RandomException;
use Overtrue\EasySms\Contracts\StrategyInterface;
use function count;
use function array_keys;
use function random_int;

/**
 * Class RandomStrategy.
 */
class RandomStrategy implements StrategyInterface
{
    /**
     * @param array $gateways
     * @return array
     */
    public function apply(array $gateways): array
    {
        try {
            $keys = array_keys($gateways);
            $n = count($keys);

            for ($i = $n - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$keys[$i], $keys[$j]] = [$keys[$j], $keys[$i]];
            }

            return $keys;
        } catch (Throwable $exception) {
            $keys = array_keys($gateways);
            shuffle($keys);
            return $keys;
        }
    }
}