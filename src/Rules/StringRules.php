<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Rules;

use TryAgainLater\Pup\Util\ValueWithErrors;

class StringRules
{
    public static function exactLength(bool $enabled, int $length): callable
    {
        return static function (ValueWithErrors $withErrors) use ($enabled, $length) {
            return $withErrors->pushErrorsIfValue(
                if: fn ($string) => $enabled && strlen($string) !== $length,
                error: "String is required to have length exactly of '$length'.",
            );
        };
    }

    public static function minLength(bool $enabled, int $length): callable
    {
        return static function (ValueWithErrors $withErrors) use ($enabled, $length) {
            return $withErrors->pushErrorsIfValue(
                if: fn ($string) => $enabled && strlen($string) <= $length,
                error: "String is required to be at least '$length' characters long,",
            );
        };
    }

    public static function maxLength(bool $enabled, int $length): callable
    {
        return static function (ValueWithErrors $withErrors) use ($enabled, $length) {
            return $withErrors->pushErrorsIfValue(
                if: fn ($string) => $enabled && strlen($string) >= $length,
                error: "String is required to be at most '$length' characters long,",
            );
        };
    }

    public static function fromBool(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->mapValueIf(
                map: fn (bool $bool) => $bool ? 'true' : 'false',
                if: is_bool(...),
                error: 'The value is not a bool.',
            );
        };
    }

    public static function fromNumber(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->mapValueIf(
                map: strval(...),
                if: fn ($value) => is_int($value) || is_float($value),
                error: 'The value is not a number.',
            );
        };
    }
}
