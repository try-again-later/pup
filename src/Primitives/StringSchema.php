<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Primitives;

use InvalidArgumentException;

use TryAgainLater\Pup\Util\ValueWithErrors;

class StringSchema extends ScalarSchema
{
    private ?int $exactLength = null;
    private ?int $minLength = null;
    private ?int $maxLength = null;

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = parent::validate($value, nothing: func_num_args() === 0 || $nothing);

        return $withErrors
            ->nextShortCircuit($this->defaultCoercion(...))
            ->next($this->applyUserDefinedTransforms(...))
            ->next($this->validateLength(...))
            ->next($this->validateMinLength(...))
            ->next($this->validateMaxLength(...));
    }

    private static function assertRequiredStringLength(int $stringLength): void
    {
        if ($stringLength < 0) {
            throw new InvalidArgumentException(
                "Requried string length cannot be a negative number (got '$stringLength')."
            );
        }
    }

    public function length(int $length): static
    {
        self::assertRequiredStringLength($length);

        $newSchema = clone $this;
        $newSchema->exactLength = $length;
        return $newSchema;
    }

    public function min(int $minLength): static
    {
        self::assertRequiredStringLength($minLength);

        $newSchema = clone $this;
        $newSchema->minLength = $minLength;
        return $newSchema;
    }

    public function max(int $maxLength): static
    {
        self::assertRequiredStringLength($maxLength);

        $newSchema = clone $this;
        $newSchema->maxLength = $maxLength;
        return $newSchema;
    }

    protected function isString(ValueWithErrors $withErrors)
    {
        $value = $withErrors->value();
        if (is_null($value) || is_string($value)) {
            return $withErrors;
        }
        return $withErrors->pushError('The value is not a string.');
    }

    protected function fromBool(ValueWithErrors $withErrors)
    {
        if (!is_bool($withErrors->value())) {
            return $withErrors->pushError('The value is not a bool.');
        }
        return $withErrors->mapValue(fn ($bool) => $bool ? 'true' : 'false');
    }

    protected function fromNumber(ValueWithErrors $withErrors)
    {
        if (is_int($withErrors->value()) || is_float($withErrors->value())) {
            return $withErrors->mapValue(fn ($number) => strval($number));
        }
        return $withErrors->pushError('The value is not a number.');
    }

    protected function defaultCoercion(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!$this->allowCoercions) {
            return $withErrors
                ->next($this->isString(...));
        }
        return $withErrors
            ->oneOf(
                $this->fromBool(...),
                $this->fromNumber(...),
            );
    }

    protected function validateLengthPredicate(
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

    protected function validateLength(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!isset($this->exactLength)) {
            return $withErrors;
        }

        return $this->validateLengthPredicate(
            $withErrors,
            fn ($length) => $length === $this->exactLength,
            fn () => "String is required to have length '$this->exactLength'.",
        );
    }

    protected function validateMinLength(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!isset($this->minLength)) {
            return $withErrors;
        }

        return $this->validateLengthPredicate(
            $withErrors,
            fn ($length) => $length >= $this->minLength,
            fn () => "String is required to be at least '$this->minLength' characters long.",
        );
    }

    protected function validateMaxLength(ValueWithErrors $withErrors): ValueWithErrors
    {
        if (!isset($this->maxLength)) {
            return $withErrors;
        }

        return $this->validateLengthPredicate(
            $withErrors,
            fn ($length) => $length <= $this->maxLength,
            fn () => "String is required to be at most '$this->maxLength' characters long.",
        );
    }
}
