<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Primitives\String;

use TryAgainLater\Pup\Util\ValueWithErrors;

class StringUtils
{
    public static function isNullableString(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (is_string($withErrors->value()) || is_null($withErrors->value())) {
            return $withErrors;
        }
        return $withErrors->pushError('The value is not a string.');
    }

    public static function fromBool(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!is_bool($withErrors->value())) {
            return $withErrors->pushError('The value is not a bool.');
        }
        return $withErrors->mapValue(fn ($bool) => $bool ? 'true' : 'false');
    }

    public static function fromNumber(ValueWithErrors $withErrors)
    {
        if (is_int($withErrors->value()) || is_float($withErrors->value())) {
            return $withErrors->mapValue(fn ($number) => strval($number));
        }
        return $withErrors->pushError('The value is not a number.');
    }

    public static function validateLengthPredicate(
        ValueWithErrors $withErrors,
        callable $lengthPredicate,
        callable $errorMessageGenerator,
    ): ValueWithErrors
    {
        if (!$lengthPredicate(strlen($withErrors->value()))) {
            return $withErrors->pushError($errorMessageGenerator($withErrors->value()));
        }
        return $withErrors;
    }
}
