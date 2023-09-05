<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

class CompareHelper
{
    private const SCALE = 2;

    /**
     * Check if both values are the same.
     *
     * @param float $value1
     * @param float $value2
     *
     * @return bool
     */
    public static function areSame(float $value1, float $value2)
    {
        if (function_exists('bccomp')) {
            return 0 === bccomp((string) $value1, (string) $value2, self::SCALE);
        }

        if (function_exists('gmp_cmp')) {
            return 0 === gmp_cmp((string) $value1, (string) $value2);
        }

        return round($value1, self::SCALE) === round($value2, self::SCALE);
    }
}
