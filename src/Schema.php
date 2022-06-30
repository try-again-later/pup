<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup;

use TryAgainLater\Pup\Rules\SchemaRules;
use TryAgainLater\Pup\Scalar\{FloatSchema, IntSchema, StringSchema};
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

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = func_num_args() === 0 || $nothing
            ? ValueWithErrors::makeNothing()
            : ValueWithErrors::makeValue($value);

        return $withErrors
            ->nextIf($this->hasDefault, SchemaRules::default($this->defaultValue))
            ->nextIf($this->required, SchemaRules::required(...))
            ->stopIf(fn ($v) => !$v->hasValue())

            ->nextIf(!$this->nullable, SchemaRules::notNullable(...))
            ->nextIf(
                $this->replaceNullWithDefault && $this->hasDefault,
                SchemaRules::replaceNullWithDefault($this->defaultValue),
            )
            ->nextIf($this->allowCoercions, $this->coerceToType(...))
            ->shortCircuit(fn ($v) => $v->next($this->checkType(...)))

            ->next(fn ($v) => $v->mapValue(...$this->userDefinedTransforms))
            ->stopIfValue(fn ($v) => is_null($v));
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

    public static function associativeArray(array $shape)
    {
        return new AssociativeArraySchema($shape);
    }
}
