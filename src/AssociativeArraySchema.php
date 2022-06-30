<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup;

use TryAgainLater\Pup\Util\ValueWithErrors;

class AssociativeArraySchema extends Schema
{
    public function __construct(private array $shape)
    {}

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = parent::validate($value, nothing: func_num_args() === 0 || $nothing);

        return $withErrors
            ->next($this->validateAllKeysArePresent(...));
    }

    protected function checkType(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->pushErrorIf(
            if: fn ($value) => !is_array($value),
            error: 'The values is not an array',
        );
    }

    protected function coerceToType(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->pushError('No coercions are available.');
    }

    protected function validateAllKeysArePresent(ValueWithErrors $withErrors): ValueWithErrors
    {
        $array = $withErrors->value();

        foreach ($this->shape as $key => $memberScheme) {
            $memberWithErrors = null;

            if (array_key_exists($key, $array)) {
                $memberWithErrors = $memberScheme->validate($array[$key]);
            } else {
                $memberWithErrors = $memberScheme->validate();
            }

            foreach ($memberWithErrors->errors() as $error) {
                $withErrors = $withErrors->pushError([$key, $error]);
            }

            // apply any transforms / default values
            if (!$memberWithErrors->hasErrors() && $memberWithErrors->hasValue()) {
                $withErrors = $withErrors->mapValue(
                    fn ($array) => [...$array, $key => $memberWithErrors->value()]
                );
            }
        }

        return $withErrors;
    }
}
