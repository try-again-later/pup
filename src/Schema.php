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

    private $checkType;
    private $coerceToType;

    public function __construct(
        callable $checkType,
        SchemaParameters $schemaParameters = new SchemaParameters(),
        ?callable $coerceToType = null,
    )
    {
        $this->required = $schemaParameters->required;
        $this->nullable = $schemaParameters->nullable;
        $this->defaultValue = $schemaParameters->defaultValue;
        $this->hasDefault = $schemaParameters->hasDefault;
        $this->replaceNullWithDefault = $schemaParameters->replaceNullWithDefault;
        $this->allowCoercions = $schemaParameters->allowCoercions;
        $this->userDefinedTransforms = $schemaParameters->userDefinedTransforms;
        $this->checkType = $checkType;

        if (isset($coerceToType)) {
            $this->coerceToType = $coerceToType;
        }
    }

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = func_num_args() === 0 || $nothing
            ? ValueWithErrors::makeNothing()
            : ValueWithErrors::makeValue($value);

        return $withErrors
            ->next(SchemaRules::default(enabled: $this->hasDefault, value: $this->defaultValue))
            ->next(SchemaRules::required(enabled: $this->required))

            // Quit early
            ->stopIf(fn (ValueWithErrors $v) => !$v->hasValue())

            ->next(SchemaRules::nullable(enabled: $this->nullable))
            ->next(SchemaRules::replaceNullWith(
                enabled: $this->replaceNullWithDefault && $this->hasDefault,
                replacement: $this->defaultValue,
            ))
            ->nextIf(
                $this->allowCoercions,
                $this->coerceToType ?? SchemaRules::defaultCoerceToType(),
            )
            ->nextShortCicruit($this->checkType)
            ->next(SchemaRules::transform(...$this->userDefinedTransforms))

            // Quit early
            ->stopIfValue(is_null(...))

            // User transforms may have changed the type of the value
            ->nextIf(
                count($this->userDefinedTransforms) > 0,
                fn (ValueWithErrors $v) => $v
                    ->nextIf(
                        $this->allowCoercions,
                        $this->coerceToType ?? SchemaRules::defaultCoerceToType(),
                    )
                    ->nextShortCicruit($this->checkType),
            );
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

    public static function string(
        SchemaParameters $schemaParameters = new SchemaParameters()
    ): StringSchema
    {
        return new StringSchema($schemaParameters);
    }

    public static function int(
        SchemaParameters $schemaParameters = new SchemaParameters()
    ): IntSchema
    {
        return new IntSchema($schemaParameters);
    }

    public static function float(
        SchemaParameters $schemaParameters = new SchemaParameters()
    ): FloatSchema
    {
        return new FloatSchema($schemaParameters);
    }

    public static function associativeArray(
        array $shape,
        SchemaParameters $schemaParameters = new SchemaParameters(),
    ): AssociativeArraySchema
    {
        return new AssociativeArraySchema(
            shape: $shape,
            schemaParameters: $schemaParameters,
        );
    }
}
