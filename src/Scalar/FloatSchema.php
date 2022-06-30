<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Util\ValueWithErrors;

class FloatSchema extends NumberSchema
{
    protected function checkType(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->pushErrorsIfValue(
            fn ($value) => !is_float($value) && !is_null($value),
            'The value is not an int.',
        );
    }

    protected function coerceToType(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->oneOf(
            self::fromBool(...),
            self::fromString(...),
            self::fromInt(...),
        );
    }

    private static function fromBool(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->mapValueIf(
            map: fn ($bool) => $bool ? 1.0 : 0.0,
            if: is_bool(...),
            error: 'The value is not a bool',
        );
    }

    private static function fromString(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->mapValueIf(
            map: floatval(...),
            if: is_string(...),
            error: 'The value is not a string.',
        );
    }

    private static function fromInt(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->mapValueIf(
            map: floatval(...),
            if: is_int(...),
            error: 'The value is not a float.',
        );
    }
}
