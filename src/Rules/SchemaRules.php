<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Rules;

use TryAgainLater\Pup\Util\ValueWithErrors;

class SchemaRules
{
    public static function default(mixed $defaultValue)
    {
        return static function (ValueWithErrors $withErrors) use ($defaultValue) {
            return $withErrors->setValue($defaultValue);
        };
    }

    public static function replaceNullWithDefault(mixed $defaultValue)
    {
        return static function (ValueWithErrors $withErrors) use ($defaultValue) {
            if (is_null($withErrors->value())) {
                return $withErrors->setValue($defaultValue);
            }
            return $withErrors;
        };
    }

    public static function required(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!$withErrors->hasValue()) {
            return $withErrors->pushErrors('Value is required.');
        }
        return $withErrors;
    }

    public static function notNullable(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (is_null($withErrors->value())) {
            return $withErrors->pushErrors('Value cannot be null.');
        }
        return $withErrors;
    }
}
