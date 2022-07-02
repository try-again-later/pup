<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Rules\FloatRules;

class FloatSchema extends NumberSchema
{
    public function __construct()
    {
        parent::__construct(FloatRules::checkType(), FloatRules::coerceToType());
    }
}
