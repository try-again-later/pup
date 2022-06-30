<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Util;

use LogicException;

/**
 * Useful when you want to make a series of independent tests for the value and collect every single
 * error instead of short-circuiting after the first one. Then you would do something like:
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
 *     ->catchAndStop()
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

    public static function makeNothing(): self
    {
        return new self;
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

    public function next(callable ...$checks): self
    {
        if ($this->stop) {
            return $this;
        }

        $currentValueWithErrors = $this;
        foreach ($checks as $check) {
            $nextValueWithErrors = $check($currentValueWithErrors);
            if (!isset($nextValueWithErrors) || !($nextValueWithErrors instanceof self)) {
                continue;
            }
            $currentValueWithErrors = $nextValueWithErrors;
        }
        return $currentValueWithErrors;
    }

    public function nextShortCircuit(callable $check): self
    {
        $nextValueWithErrors = $this->next($check);

        if (count($nextValueWithErrors->errors()) > count($this->errors())) {
            return $nextValueWithErrors->catchAndStop();
        }

        return $nextValueWithErrors;
    }

    /**
     * Peeks the callable which produces the least amount of errors.
     */
    public function oneOf(callable ...$checks): self
    {
        if ($this->stop || count($checks) === 0) {
            return $this;
        }

        $withMinErrors = null;
        foreach ($checks as $check) {
            $nextValueWithErrors = $check($this);

            // Peek the first one which does not produce any errors
            if (
                !isset($nextValueWithErrors) ||
                !($nextValueWithErrors instanceof self) ||
                count($this->errors()) == count($nextValueWithErrors->errors())
            ) {
                $withMinErrors = $nextValueWithErrors;
                break;
            }

            if (
                !isset($withMinErrors) ||
                count($withMinErrors->errors()) > count($nextValueWithErrors->errors())
            ) {
                $withMinErrors = $nextValueWithErrors;
            }
        }

        return $withMinErrors;
    }

    public function oneOfShortCircuit(callable ...$checks): self
    {
        $nextValueWithErrors = $this->oneOf(...$checks);

        if (count($nextValueWithErrors->errors()) > count($this->errors())) {
            return $nextValueWithErrors->catchAndStop();
        }

        return $nextValueWithErrors;
    }

    public function catchAndStop(?callable $onError = null): self
    {
        if (!$this->hasErrors() || $this->stop) {
            return $this;
        }
        $newValueWithErrors = clone $this;
        $newValueWithErrors->stop = true;
        if (isset($onError)) {
            $onError($newValueWithErrors);
        }
        return $newValueWithErrors;
    }

    public function stopIfValueIs(?callable $valuePredicate): self
    {
        if ($this->stop) {
            return $this;
        }
        $newValueWithErrors = clone $this;
        $newValueWithErrors->stop = boolval($valuePredicate($this->value()));
        return $newValueWithErrors;
    }

    public function stop(): self
    {
        $newValueWithErrors = clone $this;
        $newValueWithErrors->stop = true;
        return $newValueWithErrors;
    }

    public function pushError(mixed $error): self
    {
        $newValueWithErrors = clone $this;
        $newValueWithErrors->errors = [...$this->errors, $error];
        return $newValueWithErrors;
    }

    public function pushErrorIf(
        callable $if,
        callable | string $error,
    ): self {
        if ($this->stop) {
            return $this;
        }

        if ($if($this->value())) {
            $error = match (is_callable($error)) {
                true => $error($this->value()),
                false => $error,
            };

            return $this->pushError($error);
        }
        return $this;
    }

    public function dropErrors(): self
    {
        $newValueWithErrors = clone $this;
        $newValueWithErrors->errors = [];
        return $newValueWithErrors;
    }

    public function mapValue(callable ...$valueMappings): self
    {
        if ($this->stop || count($valueMappings) === 0) {
            return $this;
        }

        $newValueWithErrors = clone $this;
        foreach ($valueMappings as $valueMapping) {
            $newValueWithErrors->value = $valueMapping($newValueWithErrors->value());
        }
        return $newValueWithErrors;
    }

    public function mapValueIf(
        callable $map,
        callable $if,
        callable | string $error,
    ): self
    {
        if ($this->stop) {
            return $this;
        }

        if (!$if($this->value())) {
            $error = match (is_callable($error)) {
                true => $error($this->value()),
                false => $error,
            };

            return $this->pushError($error);
        }
        return $this->mapValue($map);
    }

    public function setValue(mixed $value): self
    {
        if ($this->stop) {
            return $this;
        }

        $newValueWithErrors = clone $this;
        $newValueWithErrors->value = $value;
        $newValueWithErrors->hasValue = true;
        return $newValueWithErrors;
    }
}
