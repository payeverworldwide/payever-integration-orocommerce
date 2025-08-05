<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Constant;

class SalutationConstant
{
    public const MR_SALUTATION = 'mr';
    public const MRS_SALUTATION = 'mrs';
    public const MS_SALUTATION = 'ms';

    /**
     * Validates and returns salutation
     *
     * @param string|null $salutation
     * @return string|null
     */
    public static function getValidSalutation(?string $salutation = null): ?string
    {
        if (!$salutation) {
            return null;
        }

        $salutation = strtolower($salutation);
        return in_array($salutation, [self::MR_SALUTATION, self::MRS_SALUTATION, self::MS_SALUTATION])
            ? $salutation : null;
    }
}
