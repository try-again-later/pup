<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Rules;

use TryAgainLater\Pup\Schema;
use TryAgainLater\Pup\Util\ValueWithErrors;

class AssociativeArrayRules
{
    public static function checkType(): callable
    {
        return static function (ValueWithErrors $withErrors) {
            return $withErrors->pushErrorsIfValue(
                if: fn ($value) => !is_array($value),
                error: 'The values is not an array',
            );
        };
    }

    /**
     * @param array<string, Schema> $shape
     */
    public static function validateShape(array $shape): callable
    {
        return static function (ValueWithErrors $arrayWithErrors) use ($shape) {
            $arrayToValidate = $arrayWithErrors->value();

            foreach ($shape as $shapeKey => $shapeScheme) {
                if (array_key_exists($shapeKey, $arrayToValidate)) {
                    $memberWithErrors = $shapeScheme->validate($arrayToValidate[$shapeKey]);
                } else {
                    $memberWithErrors = $shapeScheme->validate();
                }

                $arrayWithErrors = $arrayWithErrors->pushErrors(...array_map(
                    fn ($error) => is_string($error) ? [$shapeKey, $error] : $error,
                    $memberWithErrors->errors(),
                ));

                // apply any transforms / default values
                if ($memberWithErrors->hasValue()) {
                    $arrayWithErrors = $arrayWithErrors->mapValue(
                        fn ($array) => [
                            ...$array,
                            $shapeKey => $memberWithErrors->value(),
                        ]
                    );
                }
            }

            return $arrayWithErrors;
        };
    }
}
