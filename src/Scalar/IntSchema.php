<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Util\ValueWithErrors;

class IntSchema extends NumberSchema
{
    public static function checkType(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->pushErrorsIfValue(
                fn ($value) => !is_int($value) && !is_null($value),
                'The value is not an int.',
            );
        };
    }

    public static function coerceToType(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->tryOneOf(
                self::fromBool(...),
                self::fromString(...),
                self::fromFloat(...),
            );
        };
    }

    private static function fromBool(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->mapValueIf(
            map: fn ($bool) => $bool ? 1 : 0,
            if: is_bool(...),
            error: 'The value is not a bool',
        );
    }

    private static function fromString(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->mapValueIf(
            map: intval(...),
            if: is_string(...),
            error: 'The value is not a string.',
        );
    }

    private static function fromFloat(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->mapValueIf(
            map: intval(...),
            if: is_float(...),
            error: 'The value is not a float.',
        );
    }
}
