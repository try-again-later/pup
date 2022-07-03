<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Rules;

use TryAgainLater\Pup\Util\ValueWithErrors;

class BoolRules
{
    public static function checkType(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->pushErrorsIfValue(
                fn ($value) => !is_bool($value) && !is_null($value),
                'The value is not a bool.',
            );
        };
    }

    public static function coerceToType(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->tryOneOf(
                self::fromInt(),
                self::fromFloat(),
                self::fromString(),
            );
        };
    }

    public static function fromInt(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->mapValueIf(
                map: fn ($int) => $int === 0 ? false : true,
                if: is_int(...),
                error: 'The value is not an int.',
            );
        };
    }

    public static function fromFloat(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->mapValueIf(
                map: fn ($float) => $float === 0.0 ? false : true,
                if: is_float(...),
                error: 'The value is not a float.',
            );
        };
    }

    public static function fromString(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->mapValueIf(
                map: fn ($string) => $string === '' ? false : true,
                if: is_string(...),
                error: 'The value is not a string.',
            );
        };
    }
}
