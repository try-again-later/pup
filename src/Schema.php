<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup;

use TryAgainLater\Pup\Primitives\{FloatSchema, IntSchema, StringSchema};
use TryAgainLater\Pup\Util\ValueWithErrors;

abstract class Schema
{
    private bool $required = false;
    private bool $nullable = false;

    private mixed $defaultValue = null;
    private bool $hasDefault = false;
    private bool $replaceNullWithDefault = false;

    private bool $allowCoercions = false;
    private array $userDefinedTransforms = [];

    abstract protected function checkType(ValueWithErrors $withErrors): ValueWithErrors;

    abstract protected function coerceToType(ValueWithErrors $withErrors): ValueWithErrors;

    public static function string(): StringSchema
    {
        return new StringSchema;
    }

    public static function int(): IntSchema
    {
        return new IntSchema;
    }

    public static function float(): FloatSchema
    {
        return new FloatSchema;
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

    public function transform(callable $userDefinedTransform)
    {
        $newSchema = clone $this;
        $newSchema->userDefinedTransforms =
            [...$this->userDefinedTransforms, $userDefinedTransform];
        return $newSchema;
    }

    public function replaceNullWithDefault()
    {
        $newSchema = clone $this;
        $newSchema->replaceNullWithDefault = true;
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
            ->next($this->applyDefault(...))
            ->nextShortCircuit($this->validateRequired(...))
            ->nextShortCircuit($this->validateNullable(...))
            ->nextShortCircuit($this->applyReplaceNullWithDefault(...))
            ->nextShortCircuit($this->applyCoercions(...))
            ->next($this->applyUserDefinedTransforms(...))
            ->stopIfValueIs(fn ($value) => is_null($value));
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

    protected function applyDefault(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!$this->hasDefault) {
            return $withErrors;
        }

        if (!$withErrors->hasValue()) {
            return $withErrors->setValue($this->defaultValue);
        }

        return $withErrors;
    }

    protected function applyUserDefinedTransforms(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->mapValue(...$this->userDefinedTransforms);
    }

    protected function applyReplaceNullWithDefault(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!$this->replaceNullWithDefault || !$this->hasDefault) {
            return $withErrors;
        }

        if (is_null($withErrors->value())) {
            return $withErrors->setValue($this->defaultValue);
        }
        return $withErrors;
    }

    protected function applyCoercions(ValueWithErrors $withErrors): ValueWithErrors
    {
        // No coercions are needed if the type of the value is correct from the start
        if (!$withErrors->dropErrors()->next($this->checkType(...))->hasErrors()) {
            return $withErrors;
        }

        if (!$this->allowCoercions) {
            return $withErrors
                ->next($this->checkType(...));
        }
        return $withErrors
            ->next($this->coerceToType(...));
    }
}
