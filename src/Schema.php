<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup;

use TryAgainLater\Pup\Primitives\StringSchema;
use TryAgainLater\Pup\Util\ValueWithErrors;

class Schema
{
    private bool $required = false;
    private bool $nullable = false;

    private mixed $defaultValue = null;
    private bool $hasDefault = false;

    protected bool $allowCoercions = false;

    public static function string(): StringSchema
    {
        return new StringSchema;
    }

    public function required(): static
    {
        $newSchema = clone $this;
        $newSchema->required = true;
        return $newSchema;
    }

    public function nullable(): static
    {
        $newSchema = clone $this;
        $newSchema->nullable = true;
        return $newSchema;
    }

    public function default(mixed $defaultValue): static
    {
        $this->hasDefault = true;
        $newSchema = clone $this;
        if (is_callable($defaultValue)) {
            $newSchema->defaultValue = $defaultValue();
        } else {
            $newSchema->defaultValue = $defaultValue;
        }
        return $newSchema;
    }

    public function allowCoercions(): static
    {
        $newSchema = clone $this;
        $newSchema->allowCoercions = true;
        return $newSchema;
    }

    public function isValid(mixed $value = null): bool
    {
        return $this->validate($value, nothing: func_num_args() === 0)->hasValue();
    }

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = func_num_args() === 0 || $nothing
            ? ValueWithErrors::makeNothing()
            : ValueWithErrors::makeValue($value);

        return $withErrors
            ->next($this->setDefault(...))
            ->nextShortCircuit($this->validateRequired(...))
            ->nextShortCircuit($this->validateNullable(...));
    }

    protected function validateRequired(ValueWithErrors $withErrors): ValueWithErrors
    {
        if ($this->required && !$withErrors->hasValue()) {
            return $withErrors->pushError('Value is required.');
        }

        return $withErrors;
    }

    protected function validateNullable(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!$withErrors->hasValue() || $this->nullable) {
            return $withErrors;
        }

        if (is_null($withErrors->value())) {
            return $withErrors->pushError('Value cannot be null.');
        }

        return $withErrors;
    }

    protected function setDefault(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!$this->hasDefault) {
            return $withErrors;
        }

        if (!$withErrors->hasValue()) {
            return $withErrors->setValue($this->defaultValue);
        }

        return $withErrors;
    }
}
