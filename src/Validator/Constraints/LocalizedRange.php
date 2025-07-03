<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\RangeValidator;

class LocalizedRange extends Range
{
    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    public function validatedBy()
    {
        return RangeValidator::class;
    }
}
