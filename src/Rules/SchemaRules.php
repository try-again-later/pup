<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Rules;

use TryAgainLater\Pup\Util\ValueWithErrors;
use TryAgainLater\Pup\Attributes\Generic\Test;

class SchemaRules
{
    public static function default(bool $enabled, mixed $value): callable
    {
        return static function (ValueWithErrors $withErrors) use ($value, $enabled) {
            return $withErrors->nextIf(
                fn (ValueWithErrors $withErrors) => $enabled && !$withErrors->hasValue(),
                fn (ValueWithErrors $withErrors) => $withErrors->setValue($value),
            );
        };
    }

    public static function replaceNullWith(bool $enabled, mixed $replacement): callable
    {
        return static function (ValueWithErrors $withErrors) use ($replacement, $enabled) {
            return $withErrors->mapValueIf(
                map: fn () => $replacement,
                if: fn ($value) => $enabled && is_null($value),
            );
        };
    }

    public static function required(bool $enabled): callable
    {
        return static function (ValueWithErrors $withErrors) use ($enabled) {
            return $withErrors->pushErrorsIf(
                if: fn (ValueWithErrors $withErrors) => $enabled && !$withErrors->hasValue(),
                errors: 'Value is required.',
            );
        };
    }

    public static function nullable(bool $enabled): callable
    {
        return static function (ValueWithErrors $withErrors) use ($enabled) {
            return $withErrors->pushErrorsIf(
                if: fn (ValueWithErrors $withErrors) => !$enabled && is_null($withErrors->value()),
                errors: 'Value cannot be null.',
            );
        };
    }

    public static function transform(callable ...$transforms): callable
    {
        return static function (ValueWithErrors $withErrors) use ($transforms) {
            return $withErrors->mapValue(...$transforms);
        };
    }

    public static function defaultCoerceToType(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors;
        };
    }

    public static function test(Test ...$tests): callable
    {
        return static function (ValueWithErrors $withErrors) use ($tests) {
            if (count($tests) === 0) {
                return $withErrors;
            }

            foreach ($tests as $test) {
                $errorMessage = match (is_callable($test->message)) {
                    true => ($test->message)($withErrors->value()),
                    false => $test->message,
                };

                $withNewErrors = $withErrors->pushErrorsIfValue(
                    if: fn ($value) => !($test->check)($value),
                    errors: "Error in test '$test->name': $errorMessage",
                );

                if ($test->shortCircuit) {
                    $withErrors = $withErrors->nextShortCicruit(fn () => $withNewErrors);
                } else {
                    $withErrors = $withNewErrors;
                }
            }

            return $withErrors;
        };
    }
}
