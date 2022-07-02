<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Rules;

use TryAgainLater\Pup\Util\ValueWithErrors;

class AssociativeArrayRules
{
    public static function checkType(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->pushErrorsIfValue(
                if: fn ($value) => !is_array($value),
                error: 'The values is not an array',
            );
        };
    }
}
