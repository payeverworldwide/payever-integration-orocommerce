<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Validator\Constraints;

use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\RangeValidator;

class LocalizedRange extends Range
{
    public function __construct($options = null)
    {
        try {
            parent::__construct($options);
        } catch (ConstraintDefinitionException $e) {
            // Suppress deprecation messages
        }
    }

    public function validatedBy()
    {
        return RangeValidator::class;
    }
}
