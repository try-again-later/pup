<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Primitives;

use TryAgainLater\Pup\Schema;
use TryAgainLater\Pup\Util\ValueWithErrors;

class ScalarSchema extends Schema
{
    private ?array $allowedValues = null;

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = parent::validate($value, nothing: func_num_args() === 0 || $nothing);

        return $withErrors
            ->next($this->validateAllowedValues(...));
    }

    public function oneOf(mixed ...$allowedValues): static
    {
        $newSchema = clone $this;
        $newSchema->allowedValues = $allowedValues;
        return $newSchema;
    }

    protected function validateAllowedValues(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!isset($this->allowedValues)) {
            return $withErrors;
        }
        if (!in_array($withErrors->value(), $this->allowedValues, strict: true)) {
            $allowedValuesString = implode(', ', $this->allowedValues);
            return $withErrors->pushError(
                "Only these scalar values are allowed: $allowedValuesString"
            );
        }
        return $withErrors;
    }
}
