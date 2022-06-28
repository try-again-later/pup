<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Util;

use LogicException;

/**
 * Useful when you want to make a series of independent tests for the value and collect every single
 * error instead of short-curcuiting after the first one. Then you would do something like:
 *
 * ```php
 * $isInt =
 *     fn ($value) => is_int($value->value()) ?: $value->pushError('Expected an int.');
 *
 * $isNotNegative =
 *     fn($value) => $value->value() >= 0 ?: $value->pushError('Expected a non negative int.');
 *
 * $lessThan65536 =
 *     fn($value) => $value->value() < 65536 ?: $value->pushError('Expected <= 65535');
 *
 * $toString =
 *     fn($value) => "PORT=$value";
 *
 * $value = ValueWithErrors::makeValue(12345)
 *     ->next($isInt)
 *     ->catchAndStop
 *
 *     ->next($isNotNegative)
 *     ->next($lessThan65536)
 *     ->mapValue($toString);
 * ```
 */
class ValueWithErrors
{
    public static function makeValue(mixed $value): self
    {
        return new self(
            value: $value,
            hasValue: true,
        );
    }

    public static function makeError(mixed $error): self
    {
        return new self(
            errors: [$error],
        );
    }

    public function __construct(
        private mixed $errors = [],
        private mixed $value = null,
        private bool $hasValue = false,
        private bool $stop = false,
    )
    {}

    public function hasValue(): bool
    {
        return $this->hasValue;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function get(): array
    {
        return [$this->value, $this->errors];
    }

    public function value(): mixed
    {
        if (!$this->hasValue()) {
            throw new LogicException('There is no value inside this "expected" wrapper.');
        }
        return $this->value;
    }

    public function errors(): mixed
    {
        return $this->errors;
    }

    public function next(callable $mapping): self
    {
        if ($this->stop) {
            return $this;
        }

        $nextValueWithErrors = $mapping($this);
        if (!isset($nextValueWithErrors) || !($nextValueWithErrors instanceof self)) {
            return $this;
        }
        return $nextValueWithErrors;
    }

    public function catchAndStop(?callable $onErrors = null)
    {
        if (!$this->hasErrors() || $this->stop) {
            return $this;
        }
        $newValueWithErrors = clone $this;
        $newValueWithErrors->stop = true;
        if (isset($onErrors)) {
            $onErrors($newValueWithErrors);
        }
        return $newValueWithErrors;
    }

    public function pushError(mixed $error): self
    {
        $newValueWithErrors = clone $this;
        $newValueWithErrors->errors = [...$this->errors, $error];
        return $newValueWithErrors;
    }

    public function mapValue(callable $valueMapping): self
    {
        if ($this->stop) {
            return $this;
        }

        $newValueWithErrors = clone $this;
        $newValueWithErrors->value = $valueMapping($this->value());
        return $newValueWithErrors;
    }
}
