<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Primitives;

use InvalidArgumentException;

use TryAgainLater\Pup\Util\ValueWithErrors;
use TryAgainLater\Pup\Primitives\ScalarSchema;

class StringSchema extends ScalarSchema
{
    private ?int $exactLength = null;
    private ?int $minLength = null;
    private ?int $maxLength = null;

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = parent::validate($value, nothing: func_num_args() === 0 || $nothing);

        return $withErrors
            ->next($this->validateExactLength(...))
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

    protected function checkType(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->pushErrorIf(
            fn ($value) => !is_string($value) && !is_null($value),
            'The value is not a string',
        );
    }

    protected function coerceToType(ValueWithErrors $withErrors): ValueWithErrors
    {
        $fromBool = fn (ValueWithErrors $withErrors) =>
            $withErrors->mapValueIf(
                map: fn ($bool) => $bool ? 'true' : 'false',
                if: is_bool(...),
                error: 'The value is not a bool.',
            );

        $fromNumber = fn (ValueWithErrors $withErrors) =>
            $withErrors->mapValueIf(
                map: strval(...),
                if: fn ($value) => is_int($value) || is_float($value),
                error: 'The value is not a number.',
            );

        return $withErrors->oneOf(
            $fromBool,
            $fromNumber,
        );
    }

    protected function validateExactLength(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->pushErrorIf(
            if: fn ($string) => isset($this->exactLength) && strlen($string) !== $this->exactLength,
            error: "String is required to have length '$this->exactLength'",
        );
    }

    protected function validateMinLength(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->pushErrorIf(
            if: fn ($string) => isset($this->minLength) && strlen($string) < $this->minLength,
            error: "String is required to be at least '$this->minLength' characters long.",
        );
    }

    protected function validateMaxLength(ValueWithErrors $withErrors): ValueWithErrors
    {
        return $withErrors->pushErrorIf(
            if: fn ($string) => isset($this->maxLength) && strlen($string) > $this->maxLength,
            error: "String is required to be at most '$this->maxLength' characters long.",
        );
    }
}