<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Rules;

use TryAgainLater\Pup\Util\ValueWithErrors;

class MixedRules
{
    public static function checkType(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors;
        };
    }
}
