<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Rules;

use TryAgainLater\Pup\Util\ValueWithErrors;

class ScalarRules
{
    /**
     * @param list<int|float|string> $allowedValues
     *
     * @return callable(ValueWithErrors): ValueWithErrors
     */
    public static function oneOf(bool $enabled, array $allowedValues): callable
    {
        return static function (ValueWithErrors $withErrors) use ($enabled, $allowedValues) {
            return $withErrors->pushErrorsIfValue(
                if: fn ($value) =>
                    $enabled &&
                    !in_array($value, $allowedValues, strict: true),
                errors: static function () use ($allowedValues) {
                    $allowedValuesString = implode(', ', $allowedValues);
                    return "Only these scalar values are allowed: $allowedValuesString";
                },
            );
        };
    }
}
