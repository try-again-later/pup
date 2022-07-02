<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Rules\IntRules;

class IntSchema extends NumberSchema
{
    public function __construct()
    {
        parent::__construct(IntRules::checkType(), IntRules::coerceToType());
    }
}
