<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Util;

use LogicException;

/**
 * An immutable wrapper for a value and errors associated with it.
 *
 * Useful when you want to make a series of independent tests for the value and collect every single
 * error instead of short-circuiting after the first one. Then you would do something like:
 *
 * ```php
 * $isInt =
 *     fn ($value) => is_int($value->value()) ?: $value->pushErrors('Expected an int.');
 *
 * $isNotNegative =
 *     fn($value) => $value->value() >= 0 ?: $value->pushErrors('Expected a non negative int.');
 *
 * $lessThan65536 =
 *     fn ($value) => $value->value() < 65536 ?: $value->pushErrors('Expected <= 65535');
 *
 * $toString =
 *     fn ($value) => "PORT=$value";
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

    /**
     * Create an error without value. Use `setValue` to set the value.
     */
    public static function makeError(mixed ...$errors): self
    {
        return new self(
            errors: $errors,
        );
    }

    public static function makeNothing(): self
    {
        return new self;
    }

    public function __construct(
        private array $errors = [],
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
        return [$this->value(), $this->errors()];
    }

    public function tryGet(): array
    {
        return [$this->hasValue() ? $this->value() : null, $this->errors()];
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

    public function if(callable | bool $condition, callable $callback): self
    {
        if ($this->stop) {
            return $this;
        }

        if (is_bool($condition) && $condition || is_callable($condition) && $condition($this)) {
            return $callback($this);
        }

        return $this;
    }

    /**
     * Successively apply all of the callables to `ValueWithErrors`.
     */
    public function next(callable ...$checks): self
    {
        if ($this->stop || count($checks) === 0) {
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

    public function nextIf(callable | bool $condition, callable ...$checks): self
    {
        if (count($checks) === 0) {
            return $this;
        }

        return $this->if(
            $condition,
            fn (ValueWithErrors $v) => $v->next(...$checks),
        );
    }

    public function nextShortCicruit(callable ...$checks): self
    {
        if (count($checks) === 0) {
            return $this;
        }

        return $this->shortCircuit(
            fn (ValueWithErrors $withErrors) => $withErrors->next(...$checks)
        );
    }

    /**
     * If the callback produces any __new__ errors, then any successive operations will be ignored.
     */
    public function shortCircuit(callable $callback): self
    {
        if ($this->stop) {
            return $this;
        }

        $nextValueWithErrors = $callback($this);

        if (count($nextValueWithErrors->errors()) > count($this->errors())) {
            return $nextValueWithErrors->stop();
        }

        return $nextValueWithErrors;
    }

    /**
     * Peeks the callable which produces the least amount of errors and returns the result of
     * applying it to `ValueWithErrors`.
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

    public function tryOneOf(callable ...$checks): self
    {
        if ($this->stop || count($checks) === 0) {
            return $this;
        }

        $candidate = $this->oneOf(...$checks);
        if (count($this->errors()) < count($candidate->errors())) {
            return $this;
        }
        return $candidate;
    }

    /**
     * Same as `stopIfValue`, but unconditionally.
     */
    public function stop(): self
    {
        if ($this->stop) {
            return $this;
        }

        $newValueWithErrors = clone $this;
        $newValueWithErrors->stop = true;
        return $newValueWithErrors;
    }

    /**
     * Ignore any successive operations if the current value satisfies the given predicate.
     */
    public function stopIfValue(callable $valuePredicate): self
    {
        if ($this->stop) {
            return $this;
        }

        if ($valuePredicate($this->value())) {
            return $this->stop();
        }
        return $this;
    }

    public function stopIf(callable | bool $if): self
    {
        if ($this->stop) {
            return $this;
        }

        if (is_bool($if) && $if || is_callable($if) && $if($this)) {
            return $this->stop();
        }
        return $this;
    }

    public function pushErrors(mixed ...$errors): self
    {
        if ($this->stop || count($errors) === 0) {
            return $this;
        }

        $newValueWithErrors = clone $this;
        $newValueWithErrors->errors = [...array_values($this->errors), ...array_values($errors)];
        return $newValueWithErrors;
    }

    public function pushErrorsIfValue(callable $if, mixed ...$errors): self
    {
        if ($this->stop || count($errors) === 0) {
            return $this;
        }

        if ($if($this->value())) {
            $evaluatedErrors = array_map(
                fn ($error) => is_callable($error) ? $error($this->value()) : $error,
                $errors,
            );

            return $this->pushErrors(...$evaluatedErrors);
        }
        return $this;
    }

    public function pushErrorsIf(callable | bool $if, mixed ...$errors): self
    {
        if (count($errors) === 0) {
            return $this;
        }

        return $this->nextIf(
            $if,
            static function (self $withErrors) use ($errors) {
                $evaluatedErrors = array_map(
                    fn ($error) => is_callable($error) ? $error($this) : $error,
                    $errors,
                );

                return $withErrors->pushErrors(...$evaluatedErrors);
            },
        );
    }

    /**
     * Removes all errors.
     */
    public function dropErrors(): self
    {
        if ($this->stop) {
            return $this;
        }

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

    /**
     * If the predicate is false, pushes the error.
     */
    public function mapValueIf(
        callable $map,
        callable $if,
        callable | string | null $error = null,
    ): self
    {
        if ($this->stop) {
            return $this;
        }

        if (!$if($this->value())) {
            if (!isset($error)) {
                return $this;
            }

            $error = match (is_callable($error)) {
                true => $error($this->value()),
                false => $error,
            };

            return $this->pushErrors($error);
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
