<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Rules;

use TryAgainLater\Pup\Util\ValueWithErrors;

class NumberRules
{
    public static function min(bool $enable, int | float $value, bool $isReachable = true): callable
    {
        return static function (ValueWithErrors $withErrors) use ($value, $isReachable, $enable) {
            $error =
                'The number must be greater than ' .
                ($isReachable ? 'or equal to ' : '') .
                "$value.";

            return $withErrors->pushErrorsIfValue(
                if: fn ($number) =>
                    $enable &&
                    ($number < $value || $number === $value && !$isReachable),
                error: $error,
            );
        };
    }

    public static function max(bool $enable, int | float $value, bool $isReachable = true): callable
    {
        return static function (ValueWithErrors $withErrors) use ($value, $isReachable, $enable) {
            $error =
                'The number must be smaller than ' .
                ($isReachable ? 'or equal to ' : '') .
                "$value.";

            return $withErrors->pushErrorsIfValue(
                if: fn ($number) =>
                    $enable &&
                    ($number > $value || $number === $value && !$isReachable),
                error: $error,
            );
        };
    }
}
